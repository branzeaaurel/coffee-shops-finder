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

## Setup

```bash
docker compose up -d
```

More setup and usage details will follow as the implementation progresses.
