#!/usr/bin/env bash
#
# generate-php-class-list.sh
#
# Uses Docker to scan official PHP images and extract all built-in classes
# and interfaces. Generates per-version preset files and a combined preset.
#
# Usage:
#   ./bin/generate-php-class-list.sh              # All currently supported versions (auto-detected)
#   ./bin/generate-php-class-list.sh 8.3 8.4      # Specific versions only
#
# Requirements: Docker, curl, jq (optional, falls back to python3)
#
# The script:
#   1. Queries endoflife.date API for currently supported PHP versions
#   2. Pulls official php:X.Y-cli Docker images
#   3. Runs get_declared_classes() + get_declared_interfaces() in each
#   4. Filters to internal (built-in) classes only via ReflectionClass
#   5. Generates presets/php-global-classes.xml (union of all versions)
#   6. Generates presets/php-X.Y-classes.xml per version
#   7. Removes stale per-version presets for EOL PHP versions

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
PRESETS_DIR="$PROJECT_DIR/presets"

# --- Resolve PHP versions ---

get_supported_versions() {
	local api_json
	api_json="$(curl -sf https://endoflife.date/api/php.json)" || return 1

	# Try jq first, fall back to python3.
	if command -v jq &>/dev/null; then
		echo "$api_json" | jq -r --arg today "$(date +%Y-%m-%d)" \
			'.[] | select(.eol >= $today or .eol == false) | .cycle' | sort -V
	elif command -v python3 &>/dev/null; then
		echo "$api_json" | python3 -c "
import json, sys
from datetime import date
today = date.today().isoformat()
data = json.load(sys.stdin)
for v in data:
    if v.get('eol', '') >= today or v.get('eol') == False:
        print(v['cycle'])
" | sort -V
	else
		echo "Error: jq or python3 is required to parse the API response." >&2
		return 1
	fi
}

if [ $# -gt 0 ]; then
	VERSIONS=("$@")
	echo "Using specified PHP versions: ${VERSIONS[*]}"
else
	echo "Querying endoflife.date for currently supported PHP versions..."
	VERSIONS=()
	while IFS= read -r line; do
		[ -n "$line" ] && VERSIONS+=("$line")
	done < <(get_supported_versions)

	if [ ${#VERSIONS[@]} -eq 0 ]; then
		echo "Warning: Could not detect supported versions. Falling back to defaults." >&2
		VERSIONS=(8.2 8.3 8.4)
	fi

	echo "Supported PHP versions: ${VERSIONS[*]}"
fi

# --- Preflight ---

if ! command -v docker &>/dev/null; then
	echo "Error: Docker is required but not found." >&2
	echo "Install Docker from https://docs.docker.com/get-docker/" >&2
	exit 1
fi

if ! docker info &>/dev/null 2>&1; then
	echo "Error: Docker daemon is not running." >&2
	exit 1
fi

# PHP snippet that dumps all internal classes and interfaces, one per line.
# Stored in a file to avoid bash variable expansion issues when passing to Docker.
WORK_DIR="$(mktemp -d)"
cleanup() {
	rm -rf "$WORK_DIR"
}
trap cleanup EXIT

cat > "$WORK_DIR/scan.php" <<'PHPEOF'
<?php
$names = array_merge(get_declared_classes(), get_declared_interfaces());
$internal = [];
foreach ($names as $name) {
    $ref = new ReflectionClass($name);
    if ($ref->isInternal() && !$ref->isAnonymous()) {
        $internal[] = $ref->getName();
    }
}
sort($internal);
foreach ($internal as $name) {
    echo $name . "\n";
}
PHPEOF

ALL_CLASSES_FILE="$WORK_DIR/all_classes.txt"
touch "$ALL_CLASSES_FILE"

SCANNED_VERSIONS=()

# --- Scan each PHP version ---

for VERSION in "${VERSIONS[@]}"; do
	IMAGE="php:${VERSION}-cli"
	echo ""
	echo "Pulling $IMAGE..."

	if ! docker pull "$IMAGE" 2>&1 | tail -1; then
		echo "  Warning: Could not pull $IMAGE — skipping." >&2
		continue
	fi

	echo "Scanning $IMAGE for built-in classes and interfaces..."
	OUTPUT_FILE="$WORK_DIR/classes_${VERSION}.txt"

	if ! docker run --rm -v "$WORK_DIR/scan.php:/tmp/scan.php:ro" "$IMAGE" php /tmp/scan.php > "$OUTPUT_FILE" 2>/dev/null; then
		echo "  Warning: Failed to run scan in $IMAGE — skipping." >&2
		continue
	fi

	COUNT="$(wc -l < "$OUTPUT_FILE" | tr -d ' ')"
	echo "  Found $COUNT classes/interfaces in PHP $VERSION."

	cat "$OUTPUT_FILE" >> "$ALL_CLASSES_FILE"
	SCANNED_VERSIONS+=("$VERSION")
done

if [ ! -s "$ALL_CLASSES_FILE" ]; then
	echo "Error: No classes found from any PHP version." >&2
	exit 1
fi

VERSIONS_LABEL="$(printf '%s, ' "${SCANNED_VERSIONS[@]}" | sed 's/, $//')"

# --- Generate per-version presets ---

echo ""
echo "Generating per-version presets..."

for VERSION in "${SCANNED_VERSIONS[@]}"; do
	VERSION_FILE="$WORK_DIR/classes_${VERSION}.txt"
	PRESET_FILE="$PRESETS_DIR/php-${VERSION}-classes.xml"
	COUNT="$(wc -l < "$VERSION_FILE" | tr -d ' ')"

	{
		cat <<'HEADER'
<?xml version="1.0"?>
<ruleset name="Emrikol PHP Global Classes Preset">
HEADER

		echo "	<description>Built-in PHP $VERSION classes and interfaces for the GlobalNamespaceSniff. Auto-generated.</description>"
		echo ""
		echo "	<rule ref=\"Emrikol.Namespaces.GlobalNamespace\">"
		echo "		<properties>"
		echo "			<property name=\"known_global_classes\" type=\"array\">"

		while IFS= read -r class; do
			[ -z "$class" ] && continue
			echo "				<element value=\"$class\" />"
		done < "$VERSION_FILE"

		cat <<'FOOTER'
			</property>
		</properties>
	</rule>
</ruleset>
FOOTER
	} > "$PRESET_FILE"

	echo "  Generated: php-${VERSION}-classes.xml ($COUNT classes/interfaces)"
done

# --- Generate combined preset ---

SORTED_FILE="$WORK_DIR/sorted_classes.txt"
sort -u "$ALL_CLASSES_FILE" > "$SORTED_FILE"

TOTAL_COUNT="$(wc -l < "$SORTED_FILE" | tr -d ' ')"

COMBINED_FILE="$PRESETS_DIR/php-global-classes.xml"

{
	cat <<'HEADER'
<?xml version="1.0"?>
<ruleset name="Emrikol PHP Global Classes Preset">
HEADER

	echo "	<description>Built-in PHP classes and interfaces for the GlobalNamespaceSniff. Auto-generated from PHP $VERSIONS_LABEL.</description>"
	echo ""
	echo "	<rule ref=\"Emrikol.Namespaces.GlobalNamespace\">"
	echo "		<properties>"
	echo "			<property name=\"known_global_classes\" type=\"array\">"

	while IFS= read -r class; do
		[ -z "$class" ] && continue
		echo "				<element value=\"$class\" />"
	done < "$SORTED_FILE"

	cat <<'FOOTER'
			</property>
		</properties>
	</rule>
</ruleset>
FOOTER
} > "$COMBINED_FILE"

echo ""
echo "Generated: php-global-classes.xml ($TOTAL_COUNT unique classes/interfaces)"

# --- Remove stale per-version presets ---

echo ""
echo "Checking for stale per-version presets..."

REMOVED_ANY=false
for OLD_PRESET in "$PRESETS_DIR"/php-[0-9]*-classes.xml; do
	[ -f "$OLD_PRESET" ] || continue
	OLD_VERSION="$(basename "$OLD_PRESET" | sed 's/^php-//; s/-classes\.xml$//')"

	STILL_SUPPORTED=false
	for VERSION in "${SCANNED_VERSIONS[@]}"; do
		if [ "$OLD_VERSION" = "$VERSION" ]; then
			STILL_SUPPORTED=true
			break
		fi
	done

	if [ "$STILL_SUPPORTED" = false ]; then
		echo "  Removing stale preset: $(basename "$OLD_PRESET") (PHP $OLD_VERSION is no longer supported)"
		rm "$OLD_PRESET"
		REMOVED_ANY=true
	fi
done

if [ "$REMOVED_ANY" = false ]; then
	echo "  No stale presets found."
fi

# --- Summary ---

echo ""
echo "Summary:"
echo "  PHP versions scanned: $VERSIONS_LABEL"
echo "  Combined preset: $TOTAL_COUNT unique classes/interfaces"
echo "  Per-version presets: ${#SCANNED_VERSIONS[@]} files"
echo ""
echo "Done. Review the changes with: git diff presets/"
