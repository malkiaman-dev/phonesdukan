import re
import sys
import time
from collections import deque, defaultdict
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse, urldefrag

import urllib.request
import urllib.error


class LinkParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []

    def handle_starttag(self, tag, attrs):
        attr_map = dict(attrs)
        if tag == "a" and "href" in attr_map:
            self.links.append(attr_map["href"])
        elif tag in ("img", "script") and "src" in attr_map:
            self.links.append(attr_map["src"])
        elif tag == "link" and attr_map.get("rel") in ("stylesheet", "icon", "preload") and "href" in attr_map:
            self.links.append(attr_map["href"])


SKIP_SCHEMES = ("mailto:", "tel:", "javascript:", "data:")


def is_probably_html(content_type: str | None) -> bool:
    if not content_type:
        return False
    return "text/html" in content_type.lower()


def fetch(url: str, timeout: float = 10.0):
    req = urllib.request.Request(
        url,
        headers={
            "User-Agent": "phonesdukan-link-checker/1.0",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        },
        method="GET",
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        status = getattr(resp, "status", 200)
        final_url = resp.geturl()
        content_type = resp.headers.get("Content-Type")
        body = resp.read()
        return status, final_url, content_type, body


def normalize_url(raw: str, base: str) -> str | None:
    if not raw:
        return None
    raw = raw.strip()
    if raw.startswith(SKIP_SCHEMES):
        return None
    if raw.startswith("#"):
        return None
    # remove fragment
    raw, _frag = urldefrag(raw)
    if not raw:
        return None
    # resolve relative URLs
    return urljoin(base, raw)


def main():
    start_url = sys.argv[1] if len(sys.argv) > 1 else "http://localhost/phonesdukan/"
    max_pages = int(sys.argv[2]) if len(sys.argv) > 2 else 600
    delay = float(sys.argv[3]) if len(sys.argv) > 3 else 0.0

    start = start_url.rstrip("/") + "/"
    parsed_start = urlparse(start)
    allowed_netloc = parsed_start.netloc
    allowed_prefix = parsed_start.path.rstrip("/") + "/"

    queue = deque([start])
    seen_pages = set()
    found_from = defaultdict(set)  # url -> set(parents)
    broken = {}  # url -> (error, parents)
    checked = {}  # url -> status

    def is_internal(url: str) -> bool:
        p = urlparse(url)
        if p.netloc and p.netloc != allowed_netloc:
            return False
        # keep within /phonesdukan/ prefix to avoid crawling other vhosts paths
        if not (p.path or "").startswith(allowed_prefix.rstrip("/")):
            return False
        return True

    while queue and len(seen_pages) < max_pages:
        url = queue.popleft()
        if url in seen_pages:
            continue
        seen_pages.add(url)

        try:
            status, final_url, content_type, body = fetch(url)
            checked[url] = status
        except Exception as e:
            broken[url] = (f"FETCH_ERROR: {e.__class__.__name__}: {e}", sorted(found_from.get(url, [])))
            continue

        if status >= 400:
            broken[url] = (f"HTTP_{status}", sorted(found_from.get(url, [])))
            continue

        if delay:
            time.sleep(delay)

        if not is_probably_html(content_type):
            continue

        try:
            text = body.decode("utf-8", errors="ignore")
        except Exception:
            continue

        parser = LinkParser()
        try:
            parser.feed(text)
        except Exception:
            continue

        for raw_link in parser.links:
            normalized = normalize_url(raw_link, final_url)
            if not normalized:
                continue

            # ignore obviously external
            if not is_internal(normalized):
                continue

            found_from[normalized].add(final_url)

            # avoid crawling binary/static too deep, but still check status once
            p = urlparse(normalized)
            is_page_candidate = (p.path.endswith("/") or "." not in p.path.split("/")[-1])

            if normalized not in checked and normalized not in broken:
                # pre-check HEAD? many local setups don't support; just GET later.
                pass

            if is_page_candidate and normalized not in seen_pages:
                queue.append(normalized)

    # Now check any discovered internal URLs that we didn't fetch yet (assets, etc.)
    for url, parents in list(found_from.items()):
        if url in checked or url in broken:
            continue
        try:
            status, _final_url, _content_type, _body = fetch(url)
            checked[url] = status
            if status >= 400:
                broken[url] = (f"HTTP_{status}", sorted(parents))
        except urllib.error.HTTPError as e:
            broken[url] = (f"HTTP_{e.code}", sorted(parents))
        except Exception as e:
            broken[url] = (f"FETCH_ERROR: {e.__class__.__name__}: {e}", sorted(parents))

    # Print summary
    print(f"Checked pages: {len(seen_pages)}")
    print(f"Checked total URLs (incl assets): {len(checked) + len(broken)}")
    print(f"Broken URLs: {len(broken)}")
    print()

    if not broken:
        return 0

    # sort: status then url
    def sort_key(item):
        url, (err, _parents) = item
        m = re.match(r"HTTP_(\d+)", err)
        code = int(m.group(1)) if m else 999
        return (code, url)

    for url, (err, parents) in sorted(broken.items(), key=sort_key):
        print(f"- {err}: {url}")
        for p in parents[:5]:
            print(f"    found on: {p}")
        if len(parents) > 5:
            print(f"    ... and {len(parents) - 5} more")

    return 2


if __name__ == "__main__":
    raise SystemExit(main())

