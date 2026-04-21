#!/usr/bin/env bash
# Usage: ./scripts/bump-version.sh 1.2.3
# Updates the plugin header, the PHP constant, commits, and creates the git tag.
set -euo pipefail

VERSION="${1:-}"
if [[ -z "$VERSION" ]]; then
    echo "Usage: $0 <version>  (e.g. 1.0.19)" >&2
    exit 1
fi

PLUGIN_FILE="xpressui-wordpress-bridge.php"
README_FILE="readme.txt"

# Update WordPress plugin header
sed -i "s/^ \* Version:.*/ * Version:           ${VERSION}/" "$PLUGIN_FILE"

# Update PHP constant
sed -i "s/define( 'XPRESSUI_BRIDGE_VERSION', '[^']*' );/define( 'XPRESSUI_BRIDGE_VERSION', '${VERSION}' );/" "$PLUGIN_FILE"

# Update readme.txt Stable tag (must match plugin version or Plugin Check fails)
sed -i "s/^Stable tag:.*/Stable tag: ${VERSION}/" "$README_FILE"

echo "Updated $PLUGIN_FILE and $README_FILE to version ${VERSION}"

git add "$PLUGIN_FILE" "$README_FILE"
git commit -m "Bump version to ${VERSION}"
git tag "v${VERSION}"

echo ""
echo "Done. Push with:"
echo "  git push origin main && git push origin v${VERSION}"
