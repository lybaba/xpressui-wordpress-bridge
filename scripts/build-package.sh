#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
LIBS_DIR="$(cd "${PLUGIN_DIR}/.." && pwd)"
DIST_SLUG="xpressui-bridge"
SOURCE_SLUG="xpressui-wordpress-bridge"
SOURCE_MAIN_FILE="xpressui-wordpress-bridge.php"
DIST_MAIN_FILE="xpressui-bridge.php"
ZIP_NAME="${1:-${DIST_SLUG}.zip}"
OUTPUT_PATH="${LIBS_DIR}/${ZIP_NAME}"
STAGE_DIR="$(mktemp -d /tmp/xpressui-bridge-build.XXXXXX)"

cleanup() {
  rm -rf "${STAGE_DIR}"
}
trap cleanup EXIT

rm -f "${OUTPUT_PATH}"

cp -R "${PLUGIN_DIR}" "${STAGE_DIR}/${DIST_SLUG}"

rm -rf "${STAGE_DIR:?}/${DIST_SLUG}/.git" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.github" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.wordpress-org" \
       "${STAGE_DIR:?}/${DIST_SLUG}/scripts"
rm -f "${STAGE_DIR:?}/${DIST_SLUG}/.gitignore" \
      "${STAGE_DIR:?}/${DIST_SLUG}/.gitkeep" \
      "${STAGE_DIR:?}/${DIST_SLUG}/.gitmodules"
rm -f "${STAGE_DIR:?}/${DIST_SLUG}/WP_ORG_PRE_SUBMISSION_CHECKLIST.txt" \
      "${STAGE_DIR:?}/${DIST_SLUG}/WP_PLUGIN_CHECK.txt" \
      "${STAGE_DIR:?}/${DIST_SLUG}/render-compiled-template.php" \
      "${STAGE_DIR:?}/${DIST_SLUG}/templates/render-compiled-template.php" \
      "${STAGE_DIR:?}/${DIST_SLUG}/languages/.gitkeep"

if [[ -f "${STAGE_DIR}/${DIST_SLUG}/${SOURCE_MAIN_FILE}" ]]; then
  mv "${STAGE_DIR}/${DIST_SLUG}/${SOURCE_MAIN_FILE}" "${STAGE_DIR}/${DIST_SLUG}/${DIST_MAIN_FILE}"
fi

cd "${STAGE_DIR}"
zip -rq "${OUTPUT_PATH}" "${DIST_SLUG}"

echo "${OUTPUT_PATH}"
