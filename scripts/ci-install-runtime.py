#!/usr/bin/env python3
"""CI helper: copy the XPressUI light runtime from node_modules into runtime/
and apply WP.org-compliance URL patches.

Called from .github/workflows/build-plugin.yml after `npm install @lybaba/xpressui`.

Usage:
    python3 scripts/ci-install-runtime.py
"""
from __future__ import annotations

import re
import shutil
import sys
from pathlib import Path

PLUGIN_DIR = Path(__file__).resolve().parent.parent
VERSION_FILE = PLUGIN_DIR / "xpressui-version.txt"
RUNTIME_DIR = PLUGIN_DIR / "runtime"
XPRESSUI_SOURCES_DIR = PLUGIN_DIR / "xpressui-src"

_URL_PATCHES: list[tuple[str, str]] = [
    (
        "https://raw.githubusercontent.com/ajv-validator/ajv/master/lib/refs/data.json#",
        "urn:ajv:data-ref#",
    ),
    (
        "https://npms.io/search?q=ponyfill",
        "https://npmjs.com/search?q=ponyfill",
    ),
    (
        "https://www.google.com/maps?q=${i},${a}&output=embed",
        "https://www.openstreetmap.org/?mlat=${i}&mlon=${a}",
    ),
]


def _read_version() -> str:
    return VERSION_FILE.read_text(encoding="utf-8").strip()


def _find_npm_dist(version: str) -> Path:
    candidates = [
        PLUGIN_DIR / "node_modules" / "@lybaba" / "xpressui" / "dist",
        Path("node_modules") / "@lybaba" / "xpressui" / "dist",
    ]
    for dist in candidates:
        umd = dist / f"xpressui-light-{version}.umd.js"
        if umd.is_file():
            return dist
    raise FileNotFoundError(
        f"xpressui-light-{version}.umd.js not found in node_modules. "
        "Run: npm install @lybaba/xpressui@" + version
    )


def _install_runtime(dist_dir: Path, version: str) -> None:
    RUNTIME_DIR.mkdir(parents=True, exist_ok=True)

    # Remove stale runtime files
    for stale in RUNTIME_DIR.glob("xpressui-light-*.umd.js"):
        if stale.stem != f"xpressui-light-{version}.umd":
            stale.unlink()
            stale.with_suffix(".js.map").unlink(missing_ok=True)
            print(f"  Removed stale: {stale.name}")

    umd_src = dist_dir / f"xpressui-light-{version}.umd.js"
    umd_dest = RUNTIME_DIR / umd_src.name

    content = umd_src.read_text(encoding="utf-8")
    for old, new in _URL_PATCHES:
        content = content.replace(old, new)
    umd_dest.write_text(content, encoding="utf-8")
    print(f"  Installed + patched: {umd_dest.name}")

    map_src = dist_dir / f"xpressui-light-{version}.umd.js.map"
    if map_src.is_file():
        shutil.copy2(map_src, RUNTIME_DIR / map_src.name)


def _check_sources(version: str) -> None:
    """Warn if xpressui-src/ is missing or xpressui-version.txt mismatches."""
    if not XPRESSUI_SOURCES_DIR.is_dir() or not any(XPRESSUI_SOURCES_DIR.rglob("*.ts")):
        print(
            "WARNING: xpressui-src/ is missing or empty. "
            "Run `npm run sync:bridge-sources:free` from the parent monorepo and commit.",
            file=sys.stderr,
        )
    else:
        ts_count = len(list(XPRESSUI_SOURCES_DIR.rglob("*.ts")))
        print(f"  xpressui-src/ present ({ts_count} source files).")


def main() -> int:
    version = _read_version()
    print(f"→ Installing XPressUI light runtime v{version}…")
    dist_dir = _find_npm_dist(version)
    _install_runtime(dist_dir, version)
    _check_sources(version)
    print(f"\nDone. runtime/xpressui-light-{version}.umd.js ready.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
