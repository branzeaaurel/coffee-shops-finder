# Coffee Shops Finder — AI Assistant Context

## Stack
PHP 8.3, Symfony 7, Docker, PHPUnit, PHPStan level 8, PHP CS Fixer @Symfony ruleset.

## Architecture — 4 layers
- Domain: src/Domain/ — generic location logic, no Symfony
- Infrastructure: src/Infrastructure/ — CSV fetch, cache, parse
- Application: src/Application/ — coffee shop use cases
- HTTP: src/Controller/, src/Http/ — request/response, validation

## Key patterns to follow
- final readonly on value objects and DTOs
- declare(strict_types=1) in every file
- No AbstractController — inject only what you need
- Validation in controller via ->all() not ->get()
- Exceptions mapped in ApiExceptionSubscriber
- Rounding only in controller mapCoffeeShop()
- yield in parser — streaming, never load full CSV in memory
- Atomic write in cache — tempnam + rename

## Existing classes to know
- Coordinates, NamedLocation, LocationWithDistance — Domain value objects
- NearestLocationsFinder — top-N without full sort
- CoffeeShopProviderInterface — in Application, implemented in Infrastructure
- FindNearestCoffeeShopsHandler — orchestrates use case
- NearestCoffeeShopsController — validates x,y, calls handler, rounds distances
- ApiExceptionSubscriber — maps exceptions to JSON errors
- InvalidQueryParameterException — single exception for all invalid HTTP input

## Code style
- No tutorial comments
- No getters on readonly classes — use public properties directly
- No fromArray/toArray unless needed
- Namespace: App\ maps to src/

## Workflow
- Explain approach first. Show code in chat only.
- Wait for explicit "implement" or "ok, apply" before writing files.
- NEVER run git commands — no add, commit, push, checkout, merge.
- NEVER modify files outside src/, tests/, config/, docs/.
- NEVER touch composer.json or composer.lock without asking.
- NEVER add new dependencies without explicit approval.

## General constraints for every task
- Do not change any existing logic unless the task explicitly requires it
- Do not touch tests unless the task adds new behavior
- Do not modify files outside src/, tests/, config/, docs/
- Do not touch composer.json, composer.lock, Dockerfile, docker-compose.yml, Makefile
- NEVER run git commands
- Run make test after implementing and report results
- Show plan first, do not write files until I say "implement" or "ok, apply"
- Keep diffs small — one task at a time