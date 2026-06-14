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
RUNTIME_SRC_DIR="${PLUGIN_DIR}/xpressui-src"
RUNTIME_DIST_DIR="${RUNTIME_SRC_DIR}/dist"

cleanup() {
  rm -rf "${STAGE_DIR}"
}
trap cleanup EXIT

rm -f "${OUTPUT_PATH}"

# Build the runtime from xpressui-src only when it has not already been provided
# in runtime/ (CI installs it from the published npm package via
# ci-install-runtime.py, so the local vite build is both redundant and
# unavailable there — xpressui-src deps are not installed in CI).
if [[ -f "${RUNTIME_SRC_DIR}/package.json" ]] && ! ls "${PLUGIN_DIR}"/runtime/xpressui-*.umd.js >/dev/null 2>&1; then
  npm --prefix "${RUNTIME_SRC_DIR}" run build
  mkdir -p "${PLUGIN_DIR}/runtime"
  rm -f "${PLUGIN_DIR}/runtime"/xpressui-*.umd.js \
        "${PLUGIN_DIR}/runtime"/xpressui-*.umd.js.map
  cp "${RUNTIME_DIST_DIR}"/xpressui-*.umd.js "${PLUGIN_DIR}/runtime/"
  cp "${RUNTIME_DIST_DIR}"/xpressui-*.umd.js.map "${PLUGIN_DIR}/runtime/"
fi

cp -R "${PLUGIN_DIR}" "${STAGE_DIR}/${DIST_SLUG}"

rm -rf "${STAGE_DIR:?}/${DIST_SLUG}/.git" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.github" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.wordpress-org" \
       "${STAGE_DIR:?}/${DIST_SLUG}/scripts" \
       "${STAGE_DIR:?}/${DIST_SLUG}/node_modules" \
       "${STAGE_DIR:?}/${DIST_SLUG}/xpressui-src/dist" \
       "${STAGE_DIR:?}/${DIST_SLUG}/xpressui-src/node_modules" \
       "${STAGE_DIR:?}/${DIST_SLUG}/default-workflows/validation-playground"
rm -f "${STAGE_DIR:?}/${DIST_SLUG}/.gitignore" \
      "${STAGE_DIR:?}/${DIST_SLUG}/.gitattributes" \
      "${STAGE_DIR:?}/${DIST_SLUG}/.gitkeep" \
      "${STAGE_DIR:?}/${DIST_SLUG}/.gitmodules" \
      "${STAGE_DIR:?}/${DIST_SLUG}/package.json" \
      "${STAGE_DIR:?}/${DIST_SLUG}/package-lock.json" \
      "${STAGE_DIR:?}/${DIST_SLUG}/xpressui-version.txt" \
      "${STAGE_DIR:?}/${DIST_SLUG}/README.md"
rm -f "${STAGE_DIR:?}/${DIST_SLUG}/WP_ORG_PRE_SUBMISSION_CHECKLIST.txt" \
      "${STAGE_DIR:?}/${DIST_SLUG}/WP_PLUGIN_CHECK.txt" \
      "${STAGE_DIR:?}/${DIST_SLUG}/render-compiled-template.php" \
      "${STAGE_DIR:?}/${DIST_SLUG}/templates/render-compiled-template.php" \
      "${STAGE_DIR:?}/${DIST_SLUG}/languages/.gitkeep"
rm -rf "${STAGE_DIR:?}/${DIST_SLUG}/templates/core/time-slots-catalog"
rm -f "${STAGE_DIR:?}/${DIST_SLUG}/templates/core/fields/choice-list-time-slots.php"

if [[ -f "${STAGE_DIR}/${DIST_SLUG}/${SOURCE_MAIN_FILE}" ]]; then
  mv "${STAGE_DIR}/${DIST_SLUG}/${SOURCE_MAIN_FILE}" "${STAGE_DIR}/${DIST_SLUG}/${DIST_MAIN_FILE}"
fi

cd "${STAGE_DIR}"
zip -rq "${OUTPUT_PATH}" "${DIST_SLUG}"

echo "${OUTPUT_PATH}"
