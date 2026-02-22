#!/usr/bin/env bash
#
# install-hooks.sh — Configure git to use the project's hooks directory.
#
# Usage:
#   ./scripts/install-hooks.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_DIR"

git config core.hooksPath hooks
echo "Git hooks configured. Using hooks/ directory."
echo "Active hooks:"
ls -1 hooks/
