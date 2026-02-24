#!/usr/bin/env bash
#
# PHPCS wrapper that discovers and chains .phpcs.xml.dist configs up the
# directory tree, stopping at the outermost git root (or disk root).
#
# Child configs override parent configs — a subdirectory's .phpcs.xml.dist
# can set different prefixes, text domains, or rule properties while
# inheriting all rules from parent configs automatically.
#
# Usage:
#   phpcs-lint.sh [--fix] [path] [-- extra-phpcs-flags]
#
# Examples:
#   phpcs-lint.sh                            # Lint CWD + all child configs
#   phpcs-lint.sh plugins/my-plugin          # Lint one target (chains parent configs)
#   phpcs-lint.sh --fix plugins/my-plugin    # Auto-fix one target
#   phpcs-lint.sh -- --report=summary        # Lint all with custom report
#
# When installed via Composer (vendor/emrikol/phpcs/bin/phpcs-lint.sh), add
# to your composer.json scripts:
#
#   "lint": "bash vendor/emrikol/phpcs/bin/phpcs-lint.sh",
#   "lint:fix": "bash vendor/emrikol/phpcs/bin/phpcs-lint.sh --fix"
#

set -uo pipefail

TOOL="phpcs"
if [[ "${1:-}" == "--fix" ]]; then
	TOOL="phpcbf"
	shift
fi

# Find the phpcs/phpcbf binary.
find_binary() {
	local tool="$1"

	# Check common Composer vendor locations relative to CWD.
	if [[ -x "vendor/bin/${tool}" ]]; then
		echo "vendor/bin/${tool}"
		return
	fi

	# Check PATH.
	if command -v "${tool}" > /dev/null 2>&1; then
		command -v "${tool}"
		return
	fi

	echo "Error: ${tool} not found in vendor/bin/ or PATH" >&2
	exit 1
}

BINARY="$(find_binary "${TOOL}")"
PROJECT_ROOT="$(pwd)"

# Separate target path from extra flags.
TARGET=""
EXTRA_ARGS=()
for arg in "$@"; do
	if [[ -z "${TARGET}" && "${arg}" != -* && "${arg}" != "--" ]]; then
		TARGET="${arg}"
	elif [[ "${arg}" != "--" ]]; then
		EXTRA_ARGS+=("${arg}")
	fi
done

# Walk up from a directory, collecting .phpcs.xml.dist files.
# Stops chaining when a config contains:
#   <config name="phpcs-lint-root" value="true" />
# That config is included but nothing above it is chained in, making it
# a self-contained root (useful for plugins that are their own git repos
# nested inside a larger monorepo).
# Falls back to the outermost git root as a boundary when no root marker
# is present.
# Prints comma-separated standards string (outermost parent first, target last).
collect_standards() {
	local start_dir="$1"
	local dir="${start_dir}"
	local configs=()
	local git_root=""

	# Walk up to disk root, collecting configs.
	# Stop immediately when a config declares itself as the phpcs-lint root.
	while true; do
		local config=""
		if [[ -f "${dir}/.phpcs.xml.dist" ]]; then
			config="${dir}/.phpcs.xml.dist"
		elif [[ -f "${dir}/.phpcs.xml" ]]; then
			config="${dir}/.phpcs.xml"
		fi

		if [[ -n "${config}" ]]; then
			configs+=("${config}")
			# Root marker — this config is self-contained; don't chain above it.
			if grep -q 'name="phpcs-lint-root"' "${config}"; then
				break
			fi
		fi

		if [[ -d "${dir}/.git" ]]; then
			git_root="${dir}"
		fi

		# Stop at disk root.
		local parent
		parent="$(dirname "${dir}")"
		if [[ "${parent}" == "${dir}" ]]; then
			break
		fi
		dir="${parent}"
	done

	# Fallback: if no root marker was found, discard configs above the outermost
	# git root to avoid chaining into unrelated repositories.
	if [[ -n "${git_root}" ]]; then
		local filtered=()
		for config in "${configs[@]}"; do
			local config_dir
			config_dir="$(dirname "${config}")"
			if [[ "${config_dir}" == "${git_root}" || "${config_dir}" == "${git_root}"/* ]]; then
				filtered+=("${config}")
			fi
		done
		configs=("${filtered[@]}")
	fi

	# Reverse: outermost parent first, target directory last (last wins for overrides).
	local reversed=()
	for (( i=${#configs[@]}-1; i>=0; i-- )); do
		reversed+=("${configs[$i]}")
	done

	local IFS=','
	echo "${reversed[*]}"
}

# Run phpcs/phpcbf for a single target with chained standards.
run_lint() {
	local target="$1"
	shift

	# Resolve the directory to search for configs in.
	local config_dir
	if [[ -d "${target}" ]]; then
		config_dir="$(cd "${target}" && pwd)"
	elif [[ -f "${target}" ]]; then
		config_dir="$(cd "$(dirname "${target}")" && pwd)"
	else
		echo "Error: ${target} not found" >&2
		return 1
	fi

	local standards
	standards="$(collect_standards "${config_dir}")"

	if [[ -z "${standards}" ]]; then
		echo "No .phpcs.xml.dist found for ${target}" >&2
		return 1
	fi

	echo "==> ${TOOL} ${target} [${standards}]"
	"${BINARY}" --standard="${standards}" "${target}" "$@" < /dev/null || return $?
}

EXIT_CODE=0

if [[ -n "${TARGET}" ]]; then
	# Specific path — lint just that target.
	run_lint "${TARGET}" "${EXTRA_ARGS[@]+"${EXTRA_ARGS[@]}"}" || EXIT_CODE=$?
else
	# No path — lint root config, then discover and lint all child configs.
	echo "==> ${TOOL} (root)"
	"${BINARY}" "${EXTRA_ARGS[@]+"${EXTRA_ARGS[@]}"}" < /dev/null || EXIT_CODE=$?

	while IFS= read -r config; do
		dir="$(dirname "${config}")"
		run_lint "${dir}" "${EXTRA_ARGS[@]+"${EXTRA_ARGS[@]}"}" || EXIT_CODE=$?
	done < <(
		find "${PROJECT_ROOT}" \
			-path '*/vendor' -prune -o \
			-path '*/node_modules' -prune -o \
			\( -name '.phpcs.xml.dist' -o -name '.phpcs.xml' \) -print \
		| grep -v "^${PROJECT_ROOT}/\.phpcs\.xml" \
		| sort
	)
fi

exit "${EXIT_CODE}"
