#!/usr/bin/env bash
set -euo pipefail

PHP_FILE="auth/login-disclaimer.php"
CSS_SOURCE="$HOME/hrms-neomorphism.css"
CSS_DIR="assets/css"
CSS_FILE="$CSS_DIR/hrms-neomorphism.css"

# Verify inputs before making changes.
if [[ ! -f "$PHP_FILE" ]]; then
    echo "ERROR: $PHP_FILE not found."
    exit 1
fi

if [[ ! -f "$CSS_SOURCE" ]]; then
    echo "ERROR: $CSS_SOURCE not found."
    exit 1
fi

# Confirm that the existing embedded stylesheet is present.
if ! grep -q '<style>' "$PHP_FILE" ||
   ! grep -q '</style>' "$PHP_FILE"; then
    echo "ERROR: Expected embedded style block was not found."
    echo "No changes made."
    exit 1
fi

# Create a timestamped backup.
BACKUP="${PHP_FILE}.bak.$(date +%Y%m%d_%H%M%S)"
cp -p "$PHP_FILE" "$BACKUP"

mkdir -p "$CSS_DIR"
cp "$CSS_SOURCE" "$CSS_FILE"

# Replace only the embedded CSS block, retaining the PHP and HTML.
# Use a temporary file and move it into place only on success.
TMP_FILE=$(mktemp "${PHP_FILE}.tmp.XXXXXX")
trap 'rm -f "$TMP_FILE"' EXIT

python3 - "$PHP_FILE" "$TMP_FILE" <<'PY'
import re
import sys

source, destination = sys.argv[1:3]

with open(source, "r", encoding="utf-8") as f:
    content = f.read()

pattern = re.compile(r"<style>\s*.*?\s*</style>", re.DOTALL | re.IGNORECASE)
replacement = '<link rel="stylesheet" href="/assets/css/hrms-neomorphism.css">'

updated, count = pattern.subn(replacement, content, count=1)

if count != 1:
    raise SystemExit("ERROR: Could not uniquely replace the embedded CSS block.")

with open(destination, "w", encoding="utf-8") as f:
    f.write(updated)
PY

# Validate PHP syntax before applying the change.
if command -v php >/dev/null 2>&1; then
    if ! php -l "$TMP_FILE"; then
        echo "ERROR: PHP syntax check failed. Original file retained."
        exit 1
    fi
else
    echo "WARNING: PHP CLI unavailable; syntax was not checked."
fi

# Apply the updated PHP file.
cat "$TMP_FILE" > "$PHP_FILE"
rm -f "$TMP_FILE"
trap - EXIT

echo
echo "SUCCESS: Neomorphism stylesheet installed."
echo "PHP backup: $BACKUP"
echo "CSS file:   $CSS_FILE"
echo
echo "Verify the installation with:"
echo "grep -n 'hrms-neomorphism.css' $PHP_FILE"
echo "php -l $PHP_FILE"
