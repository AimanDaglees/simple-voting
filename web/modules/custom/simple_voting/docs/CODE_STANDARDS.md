# Code standards and automated tests

## Composer validation

```bash
lando composer update --lock --no-interaction
lando composer validate --strict
```

## PHP syntax

```bash
lando lint-module
```

## PHP_CodeSniffer

```bash
lando composer check:phpcs
```

## PHPStan

```bash
lando composer check:phpstan
```

## PHPUnit

Create a separate test database:

```bash
lando mysql -uroot -e "
CREATE DATABASE IF NOT EXISTS drupal11_test;
GRANT ALL PRIVILEGES ON drupal11_test.* TO 'drupal11'@'%';
FLUSH PRIVILEGES;
"
```

Run the module tests:

```bash
lando ssh -s appserver -c '
  mkdir -p /tmp/browser_output
  SIMPLETEST_DB="mysql://drupal11:drupal11@database/drupal11_test"               SIMPLETEST_BASE_URL="http://appserver"               BROWSERTEST_OUTPUT_DIRECTORY="/tmp/browser_output"               /app/vendor/bin/phpunit                 -c /app/web/core/phpunit.xml.dist                 /app/web/modules/custom/simple_voting/tests/src
'
```

## Cache rebuild

```bash
lando drush cr
```

## Database archive validation

```bash
gzip -t database/simple-voting.sql.gz
```
