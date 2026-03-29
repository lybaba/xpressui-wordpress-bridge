#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
REPO_ROOT="$(cd "${PLUGIN_DIR}/../.." && pwd)"
WORKFLOW_SLUG="validation-playground"
WORKFLOW_DIR="${PLUGIN_DIR}/default-workflows/${WORKFLOW_SLUG}"
LIBS_DIR="$(cd "${PLUGIN_DIR}/.." && pwd)"
ZIP_NAME="${1:-${WORKFLOW_SLUG}-test.zip}"
OUTPUT_PATH="${LIBS_DIR}/${ZIP_NAME}"
DOWNLOADS_DIR="/mnt/c/Users/ly_ba/Downloads"
COPY_TO_DOWNLOADS="${XPRESSUI_COPY_TO_DOWNLOADS:-1}"
STAGE_DIR="$(mktemp -d /tmp/${WORKFLOW_SLUG}-build.XXXXXX)"

cleanup() {
  rm -rf "${STAGE_DIR}"
}
trap cleanup EXIT

python3 "${REPO_ROOT}/scripts/generate-default-workflow-template-context.py" "${WORKFLOW_SLUG}"

rm -f "${OUTPUT_PATH}"
cp -R "${WORKFLOW_DIR}" "${STAGE_DIR}/${WORKFLOW_SLUG}"

cd "${STAGE_DIR}"
zip -rq "${OUTPUT_PATH}" "${WORKFLOW_SLUG}"

if [[ "${COPY_TO_DOWNLOADS}" == "1" && -d "${DOWNLOADS_DIR}" ]]; then
  cp "${OUTPUT_PATH}" "${DOWNLOADS_DIR}/${ZIP_NAME}"
fi

echo "${OUTPUT_PATH}"
