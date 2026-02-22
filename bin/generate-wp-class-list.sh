#!/usr/bin/env bash
#
# generate-wp-class-list.sh
#
# Downloads the latest WordPress nightly build and regenerates the preset XML
# files containing WordPress class lists. Run this whenever a new WordPress
# version is released to keep the presets up to date.
#
# Usage:
#   ./bin/generate-wp-class-list.sh
#
# The script is fully automated:
#   1. Downloads https://wordpress.org/nightly-builds/wordpress-latest.zip
#   2. Extracts and scans all PHP files for WP_* class definitions
#   3. Regenerates presets/wordpress-classes.xml (TypeDeclarationSniff)
#   4. Prints a summary of changes
#
# Requirements: curl, unzip, grep, sed, sort

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
PRESETS_DIR="$PROJECT_DIR/presets"

WP_ZIP_URL="https://wordpress.org/nightly-builds/wordpress-latest.zip"
WORK_DIR="$(mktemp -d)"

cleanup() {
	rm -rf "$WORK_DIR"
}
trap cleanup EXIT

# --- Download and extract WordPress ---

echo "Downloading WordPress nightly build..."
curl -sL -o "$WORK_DIR/wordpress.zip" "$WP_ZIP_URL"

echo "Extracting..."
unzip -qo "$WORK_DIR/wordpress.zip" -d "$WORK_DIR"

WP_DIR="$WORK_DIR/wordpress"
if [ ! -d "$WP_DIR" ]; then
	echo "Error: Expected wordpress/ directory not found in archive." >&2
	exit 1
fi

# --- Detect version ---

WP_VERSION="$(sed -n "s/.*\$wp_version *= *'\([^']*\)'.*/\1/p" "$WP_DIR/wp-includes/version.php" 2>/dev/null)"
WP_VERSION="${WP_VERSION:-unknown}"
echo "WordPress version: $WP_VERSION"

# --- Scan for WP_* classes ---

echo "Scanning for WP_* class definitions..."
CLASS_LIST="$(grep -rEho 'class\s+WP_[a-zA-Z0-9_]+' "$WP_DIR" 2>/dev/null \
	| sed 's/class[[:space:]]*//' \
	| sort -u)"

CLASS_COUNT="$(echo "$CLASS_LIST" | wc -l | tr -d ' ')"
echo "Found $CLASS_COUNT WP_* classes."

# --- Generate presets/wordpress-classes.xml ---

PRESET_FILE="$PRESETS_DIR/wordpress-classes.xml"

{
	cat <<'HEADER'
<?xml version="1.0"?>
<ruleset name="Emrikol WordPress Classes Preset">
HEADER

	echo "	<description>Known WordPress classes for return type validation. Auto-generated from WordPress $WP_VERSION. Include this preset to enable type validation with WordPress class support.</description>"
	echo ""
	echo "	<rule ref=\"Emrikol.Functions.TypeDeclaration\">"
	echo "		<properties>"
	echo "			<property name=\"validate_types\" value=\"true\" />"
	echo "			<property name=\"known_classes\" type=\"array\">"

	echo "$CLASS_LIST" | while IFS= read -r class; do
		[ -z "$class" ] && continue
		echo "				<element value=\"$class\" />"
	done

	cat <<'FOOTER'
			</property>
		</properties>
	</rule>
</ruleset>
FOOTER
} > "$PRESET_FILE"

echo ""
echo "Generated: $PRESET_FILE"
echo "  Classes: $CLASS_COUNT"
echo "  WordPress version: $WP_VERSION"
echo ""
echo "Done. Review the changes with: git diff presets/"
