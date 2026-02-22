# Contributing

This repository is published **as-is** under the GPL-3.0 license. Pull requests are only accepted from collaborators who have been explicitly added to this repository.

If you'd like to make changes, please **fork** the project and continue development under the GPL.

For questions, ideas, or discussion, use [GitHub Discussions](https://github.com/emrikol/phpcs/discussions).

---

## For Collaborators

### Setup

```bash
git clone git@github.com:emrikol/phpcs.git
cd phpcs
composer install
git config core.hooksPath hooks
```

### Requirements

- PHP >= 8.0
- Composer
- PHP CodeSniffer >= 3.7 (installed via Composer)

### Running the Sniffs Locally

```bash
# Check the sniffs against a test file
vendor/bin/phpcs --standard=Emrikol path/to/test.php

# Auto-fix
vendor/bin/phpcbf --standard=Emrikol path/to/test.php
```

### Code Style

The sniff source files use **WordPress coding style**:

- Tabs for indentation
- Spaces inside parentheses and brackets
- WordPress-style braces

### Updating the WordPress Class List

Run the generator script before any release that should include an updated class list:

```bash
./bin/generate-wp-class-list.sh
```

See the [README](README.md#updating-the-wordpress-class-list) for details.

### Release Procedure

1. Update `CHANGELOG.md` with a dated entry following [Keep a Changelog](https://keepachangelog.com/) format.
2. Commit the changelog update.
3. Create and push a version tag:

```bash
git tag -a v0.2.0 -m "v0.2.0 — Description"
git push origin v0.2.0
```

The pre-push hook will validate the changelog entry before allowing the tag push.
