#!/usr/bin/env python3
"""Smoke checks for EDFM GA4 privacy + Basic Consent Mode eligibility.

These checks avoid Google's live service. Server-rendered HTML must never load
Google Analytics directly; eligible public pages only expose a first-party meta
marker that MediaWiki:Common.js may use after optional consent is granted.
"""
from __future__ import annotations

import sys
import urllib.error
import urllib.request

BASE = "https://edfieldmanual.com"
GA_SRC = "googletagmanager.com/gtag/js?id=GAID_PLACEHOLDER"
GA_CFG = "gtag('config','GAID_PLACEHOLDER'"
GA_META = 'name="edfm-ga4-measurement-id" content="GAID_PLACEHOLDER"'

CASES = [
    ("/wiki/Main_Page", True, "public article"),
    ("/wiki/Felicity_Farseer", True, "public article"),
    ("/wiki/Special:Search?search=engineer", True, "public search"),
    ("/wiki/User:Sythan", False, "User namespace/profile"),
    ("/wiki/Special:UserLogin", False, "login"),
    ("/wiki/Special:CreateAccount", False, "account creation"),
    ("/wiki/Special:ChangeCredentials", False, "credential change"),
    ("/wiki/Special:PasswordReset", False, "password recovery"),
    ("/wiki/Special:ConfirmEmail", False, "email confirmation"),
    ("/wiki/Special:Preferences", False, "account preferences"),
    ("/wiki/special:pluggableauthlogin?code=abc&state=xyz", False, "OAuth callback"),
    ("/wiki/Main_Page?code=abc&state=xyz", False, "sensitive query defense"),
    ("/wiki/Main_Page?returnto=User:Sythan", False, "returnto leakage defense"),
]


def fetch(path: str) -> str:
    req = urllib.request.Request(BASE + path, headers={"User-Agent": "EDFM-GA4-consent-smoke/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            return resp.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        return exc.read().decode("utf-8", errors="replace")


def main() -> int:
    failures: list[str] = []
    for path, expected_meta, label in CASES:
        html = fetch(path)
        src_count = html.count(GA_SRC)
        cfg_count = html.count(GA_CFG)
        meta_count = html.count(GA_META)
        no_google_loader = src_count == 0 and cfg_count == 0
        meta_ok = (meta_count == 1) if expected_meta else (meta_count == 0)
        print(f"{path:65} expected={'eligible' if expected_meta else 'blocked':8} meta={meta_count} src={src_count} cfg={cfg_count} {label}")
        if not no_google_loader:
            failures.append(f"{path}: server emitted Google tag/config before consent (src={src_count}, cfg={cfg_count})")
        if not meta_ok:
            failures.append(f"{path}: expected {'one eligibility marker' if expected_meta else 'no eligibility marker'}, got meta={meta_count}")
    if failures:
        print("\nFAILURES:", file=sys.stderr)
        for failure in failures:
            print(f"- {failure}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
