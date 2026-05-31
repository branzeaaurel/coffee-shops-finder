# Coffee Shops Finder API

A small REST API that returns the three nearest coffee shops for a given user location. Built with PHP 8.3, Symfony 7, and Docker.

## Setup

```bash
git clone git@github.com:branzeaaurel/coffee-shops-finder.git
cd coffee-shops-finder
make up
make install
make serve
```

In another terminal:

```bash
curl "http://localhost:8080/api/coffee-shops/nearest?x=47.6&y=-122.4"
```

Expected response:

```json
[
  {
    "name": "Starbucks Seattle2",
    "location": {
      "x": 47.5869,
      "y": -122.3368
    },
    "distance": 0.0645
  },
  {
    "name": "Starbucks Seattle",
    "location": {
      "x": 47.5809,
      "y": -122.316
    },
    "distance": 0.0861
  },
  {
    "name": "Starbucks SF",
    "location": {
      "x": 37.5209,
      "y": -122.334
    },
    "distance": 10.0793
  }
]
```

## Manual testing

`docs/requests.http` contains example requests for the endpoint: the happy path, missing parameters, non-numeric input, array-style input, and non-finite values. It works well with JetBrains HTTP Client, VS Code REST Client, or as a copy-paste reference for `curl`.

To browse the API spec interactively, run `make serve` and open
http://localhost:8080/api-docs.html in a browser.

Useful log commands:

```bash
make logs                 # tail dev.log
make logs-error           # grep error/critical/exception lines
make logs-search q=fetch  # search dev.log for a term
```

## Configuration

Runtime configuration lives in `.env`:

- `CSV_FETCH_URL` — upstream CSV URL.
- `CSV_FETCH_TIMEOUT_SECONDS` — HTTP timeout per attempt, defaults to 5.
- `CSV_CACHE_TTL_SECONDS` — how long the local cache file is considered fresh, defaults to 300.

Use `.env.local` for local overrides.

## Architecture

The code is split into four small layers.

The **domain** contains the generic location logic: coordinates, named locations, distances, the Euclidean distance calculator, and nearest-location selection. It has no Symfony, HTTP, or filesystem concerns. The finder keeps only the current top results instead of sorting the whole dataset.

The **infrastructure** layer owns the CSV integration. It fetches the remote file, caches the raw CSV locally, and parses it row by row with `fgetcsv`. The parser yields `NamedLocation` objects, so it does not need to load the full file into memory. Malformed rows are skipped and logged with a reason code.

The **application** layer contains the use case. `FindNearestCoffeeShopsHandler` gets the coffee shops from the provider, delegates nearest-location selection to the domain service, and maps the result to `NearestCoffeeShop`. Distances stay raw here.

The **HTTP** layer contains the controller and exception subscriber. The controller validates query parameters, creates `Coordinates`, calls the handler, rounds distances to four decimals, and returns a manual `JsonResponse`. The subscriber turns known exceptions into stable JSON errors and hides internal details for unexpected failures.

## Key decisions and trade-offs

The endpoint is small, so I kept request validation and response mapping explicit in the controller. Symfony Form and Serializer would be fine for a larger API, but here they would add more setup than value.

The upstream CSV currently has no header, even though the challenge describes `Name,X,Y`. The parser supports both formats: it skips a `Name,X,Y` header when present, otherwise it treats the first row as data.

Distance is calculated with the Euclidean formula because the challenge treats coordinates as points on a plane. For real geographic distances, I would swap the calculator for a Haversine implementation behind the same `DistanceCalculatorInterface`.

The provider uses a stale cache fallback. If the upstream CSV is temporarily unavailable but an older local file exists, the API can still return results. If there is no cache and the upstream fails, the API returns `503`.

Cache writes are atomic: the new CSV is written to a temporary file and then renamed over the old cache file. That avoids partially written cache files.

## Running checks

```bash
make test       # PHPUnit
make stan       # PHPStan level 8
make cs-check   # PHP CS Fixer (Symfony preset)
```

## What I would add with more time

- Exponential backoff with jitter between HTTP retries instead of immediate attempts.
- A circuit breaker on the upstream fetch so transient outages don't slow down every request.
- A shared cache (Redis or similar) so multiple instances aren't each maintaining their own local CSV file.
- A Haversine distance implementation, switched in by changing one DI alias.
- A GitHub Actions workflow running `make install && make test && make stan && make cs-check` on every push.
