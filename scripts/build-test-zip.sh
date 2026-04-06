#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ZIP_NAME="${1:-xpressui-wordpress-bridge.zip}"

bash "${SCRIPT_DIR}/build-package.sh" "${ZIP_NAME}"
