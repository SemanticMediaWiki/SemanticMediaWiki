# Development environment

Set up a local development environment for Semantic MediaWiki (SMW) using Docker. The environment is powered by [docker-compose-ci](https://github.com/gesinn-it-pub/docker-compose-ci) (included as a Git submodule in `build/`) and mirrors the GitHub Actions CI pipeline — tests that pass locally will pass in CI.

## Prerequisites

- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)

## Quick start

```sh
git clone --recurse-submodules https://github.com/SemanticMediaWiki/SemanticMediaWiki.git
cd SemanticMediaWiki
make install
```

This builds the Docker containers, installs MediaWiki (MW 1.43, PHP 8.1, MariaDB 11.2 by default), and runs `composer update` inside the extension.

Verify the setup:

```sh
make composer-test
```

This runs lint, PHPCS, and all PHPUnit test suites — the same sequence as CI.

## Browsing the wiki

`make install` does not expose any ports by default. To access the wiki in a browser, create a compose override file and reinstall:

```sh
cat > build/docker-compose.override.yml <<'EOF'
services:
  wiki:
    ports:
      - 8080:8080
EOF
make destroy install
```

The wiki is then available at [http://localhost:8080](http://localhost:8080) (credentials: **WikiSysop** / **wiki4everyone**). The override file only needs to be created once.

## Seeding demo data

The dev wiki starts empty. To populate it with sample pages that exercise SMW features, run the seeder script:

```sh
docker exec semanticmediawiki-mysql-wiki-1 php /var/www/html/maintenance/run.php \
  SemanticMediaWiki:seedDemoData --force
```

This creates ~147 pages tracked in `Category:Seed data`. To remove them:

```sh
docker exec semanticmediawiki-mysql-wiki-1 php /var/www/html/maintenance/run.php \
  SemanticMediaWiki:seedDemoData --force --clear-only
```

## Syncing local changes

The extension is copied into the container at build time, not bind-mounted. Local edits are **not** reflected automatically. Copy files in before testing and copy fixes back after auto-formatting:

```sh
# Copy a local file into the container
docker cp path/to/file.php \
  semanticmediawiki-mysql-wiki-1:/var/www/html/extensions/SemanticMediaWiki/path/to/file.php

# Copy a file back from the container
docker cp semanticmediawiki-mysql-wiki-1:/var/www/html/extensions/SemanticMediaWiki/path/to/file.php \
  path/to/file.php
```

## Configuration

The Makefile accepts these variables:

| Variable | Default | Description |
|---|---|---|
| `MW_VERSION` | `1.43` | MediaWiki version |
| `PHP_VERSION` | `8.1` | PHP version |
| `DB_TYPE` | `mysql` | Database type |
| `DB_IMAGE` | `mariadb:11.2` | Database Docker image |

Override on the command line or in a `.env` file at the project root:

```sh
# Command line
make install MW_VERSION=1.44 PHP_VERSION=8.3

# Or via .env file
echo 'MW_VERSION=1.44' >> .env
echo 'PHP_VERSION=8.3' >> .env
make install
```

To switch versions, destroy and reinstall:

```sh
make destroy install MW_VERSION=1.45 PHP_VERSION=8.4 DB_IMAGE="mariadb:11.8"
```

## Running tests

### Inside the container

From a shell inside the container (`make bash`), run tests directly:

```sh
cd /var/www/html/extensions/SemanticMediaWiki

# Full test suite (lint + PHPCS + PHPUnit)
composer test

# Unit tests only
composer phpunit:unit

# Integration tests only
composer phpunit:integration
```

### Via Make

```sh
# Full test suite (lint + PHPCS + PHPUnit)
make composer-test

# Unit tests only
make composer-test COMPOSER_PARAMS="-- --testsuite=semantic-mediawiki-unit"
```

### Via docker exec

To run individual test suites or classes from your host machine (the container name follows the pattern `semanticmediawiki-<DB_TYPE>-wiki-1`):

```sh
# Unit tests
docker exec semanticmediawiki-mysql-wiki-1 bash -c \
  "cd /var/www/html/extensions/SemanticMediaWiki && composer phpunit:unit -- --no-coverage"

# Integration tests
docker exec semanticmediawiki-mysql-wiki-1 bash -c \
  "cd /var/www/html/extensions/SemanticMediaWiki && composer phpunit:integration -- --no-coverage"

# Single test class
docker exec semanticmediawiki-mysql-wiki-1 bash -c \
  "cd /var/www/html/extensions/SemanticMediaWiki && composer phpunit -- --no-coverage --filter 'TestClassName'"
```

## PHPCS

Run analysis (lint + code style):

```sh
docker exec semanticmediawiki-mysql-wiki-1 bash -c \
  "cd /var/www/html/extensions/SemanticMediaWiki && composer analyze"
```

Auto-fix code style violations:

```sh
docker exec semanticmediawiki-mysql-wiki-1 bash -c \
  "cd /var/www/html/extensions/SemanticMediaWiki && composer fix"
```

After running `composer fix`, remember to [copy fixed files back](#syncing-local-changes) to your local checkout.

**Caution:** `composer analyze` exits 0 even with warnings. Read the full output — warnings must also be fixed.

## Interactive shell

```sh
make bash
# or:
docker exec -it semanticmediawiki-mysql-wiki-1 bash
```

## CI

CI runs on [GitHub Actions](https://github.com/SemanticMediaWiki/SemanticMediaWiki/actions) using the same Docker and Makefile setup. The workflow is defined in [`.github/workflows/main.yml`](../.github/workflows/main.yml).

### Test matrix

| MediaWiki | PHP | Database | Status |
|---|---|---|---|
| 1.43 | 8.2 | MariaDB 11.2 | Stable (+ coverage) |
| 1.43 | 8.3 | MariaDB 11.2 | Stable |
| 1.44 | 8.3 | MariaDB 11.2 | Experimental |
| 1.44 | 8.3 | MariaDB 11.8 | Experimental |
| 1.45 | 8.4 | MariaDB 11.8 | Experimental |

CI runs `make ci`, which calls `make install` followed by `composer test` and `npm test`.
