# Coffee Shops Finder API

REST API that returns the three closest coffee shops for a given user location.

## Problem

The API receives user coordinates and returns the three nearest coffee shops, ordered from closest to farthest, including the distance from the user.

Coffee shops are loaded from a remote CSV file:

```text
Name,X,Y
```

Data quality may vary — malformed rows are skipped gracefully.
Distances are rounded to four decimal places.
All coordinates lie on a plane.

## Example

Input coordinates: `X=47.6`, `Y=-122.4`

Expected result:

```text
Starbucks Seattle2
Starbucks Seattle
Starbucks SF
```

## Endpoint

```http
GET /api/coffee-shops/nearest?x=47.6&y=-122.4
```

Response:

```json
[
  {
    "name": "Starbucks Seattle2",
    "location": {
      "x": 47.6,
      "y": -122.4
    },
    "distance": 0.0
  }
]
```

## Stack

PHP 8.3 · Symfony 7 · Docker

## Running

> Requirements: Docker and Docker Compose v2. No PHP needed on the host.

```bash
git clone <repo-url> coffee-shops-finder
cd coffee-shops-finder

make up        # build image and start the container
make install   # install PHP dependencies via Composer
make test      # run PHPUnit test suite
make stan      # run PHPStan static analysis
make cs-check  # check code style (PHP CS Fixer)
make cs-fix    # auto-fix code style violations
make shell     # open a shell inside the container
make down      # stop and remove the container
```

### Fallback (local PHP, no Docker)

If you have PHP 8.3+ and Composer installed locally:

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer check --diff
```
