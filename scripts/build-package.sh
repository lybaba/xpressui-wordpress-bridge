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

# The PHP loads the standard (non-light) runtime named after the runtime version
# in xpressui-version.txt: runtime/xpressui-<version>.umd.js. Bundle exactly that.
RUNTIME_VERSION="$(tr -d '[:space:]' < "${PLUGIN_DIR}/xpressui-version.txt")"
EXPECTED_RUNTIME="${PLUGIN_DIR}/runtime/xpressui-${RUNTIME_VERSION}.umd.js"

# Provide the exact expected runtime when it is not already present. A stale or
# light-tier file in runtime/ must not be reused (it would 404 at runtime).
# Preference: (1) the monorepo's already-built standard runtime
# (libs/xpressui/dist) — it bundles every dependency (qrcode, etc.); else
# (2) build from the shipped xpressui-src sources, installing deps first and
# forcing the standard tier. CI installs it from the published npm package via
# ci-install-runtime.py before calling this, so both branches are skipped there.
MONOREPO_RUNTIME="${LIBS_DIR}/xpressui/dist/xpressui-${RUNTIME_VERSION}.umd.js"
if [[ ! -f "${EXPECTED_RUNTIME}" ]]; then
  mkdir -p "${PLUGIN_DIR}/runtime"
  rm -f "${PLUGIN_DIR}/runtime"/xpressui-*.umd.js "${PLUGIN_DIR}/runtime"/xpressui-*.umd.js.map
  if [[ -f "${MONOREPO_RUNTIME}" ]]; then
    cp "${MONOREPO_RUNTIME}" "${EXPECTED_RUNTIME}"
    [[ -f "${MONOREPO_RUNTIME}.map" ]] && cp "${MONOREPO_RUNTIME}.map" "${EXPECTED_RUNTIME}.map"
  elif [[ -f "${RUNTIME_SRC_DIR}/package.json" ]]; then
    # Install deps (qrcode, etc.) so the full build resolves, and force the
    # standard tier so an ambient XPRESSUI_RUNTIME_TIER=light cannot make it light.
    ( cd "${RUNTIME_SRC_DIR}" && npm install --no-audit --no-fund && XPRESSUI_RUNTIME_TIER=standard npm run build )
    cp "${RUNTIME_DIST_DIR}/xpressui-${RUNTIME_VERSION}.umd.js" "${EXPECTED_RUNTIME}"
    [[ -f "${RUNTIME_DIST_DIR}/xpressui-${RUNTIME_VERSION}.umd.js.map" ]] \
      && cp "${RUNTIME_DIST_DIR}/xpressui-${RUNTIME_VERSION}.umd.js.map" "${EXPECTED_RUNTIME}.map"
  fi
fi

# Never ship the light runtime in the unified plugin.
rm -f "${PLUGIN_DIR}/runtime"/xpressui-light-*.umd.js "${PLUGIN_DIR}/runtime"/xpressui-light-*.umd.js.map

# Fail loudly instead of shipping a zip whose form will 404 on the runtime.
if [[ ! -f "${EXPECTED_RUNTIME}" ]]; then
  echo "ERROR: bundled runtime missing: runtime/xpressui-${RUNTIME_VERSION}.umd.js" >&2
  echo "       From the monorepo root run 'npm run build:wordpress-runtime' (builds the" >&2
  echo "       standard runtime and syncs it here), or run scripts/ci-install-runtime.py." >&2
  exit 1
fi

cp -R "${PLUGIN_DIR}" "${STAGE_DIR}/${DIST_SLUG}"

rm -rf "${STAGE_DIR:?}/${DIST_SLUG}/.git" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.github" \
       "${STAGE_DIR:?}/${DIST_SLUG}/.wordpress-org" \
       "${STAGE_DIR:?}/${DIST_SLUG}/docs" \
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
