#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
LIBS_DIR="$(cd "${PLUGIN_DIR}/.." && pwd)"
ZIP_NAME="${1:-xpressui-wordpress-bridge-test.zip}"
OUTPUT_PATH="${LIBS_DIR}/${ZIP_NAME}"

rm -f "${OUTPUT_PATH}"

cd "${LIBS_DIR}"

zip -r "${OUTPUT_PATH}" xpressui-wordpress-bridge \
  --exclude "*/render-compiled-template.php" \
  --exclude "*/WP_ORG_PRE_SUBMISSION_CHECKLIST.txt" \
  --exclude "*/WP_PLUGIN_CHECK.txt" \
  --exclude "*/.git" \
  --exclude "*/.git/*" \
  --exclude "*/.github" \
  --exclude "*/.github/*"

echo "${OUTPUT_PATH}"
