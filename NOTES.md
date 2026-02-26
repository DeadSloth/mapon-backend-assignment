# Fuel API – Development Guide

Requirement: The user must have composer and docker installed.

Starting the project:
```
docker compose up
```

This will run the following:
- MySQL Database
- The application on localhost:80
- phpMyAdmin interface on localhost:8080

If the user has composer installed, whole project can be managed via composer scripts, including static code analysis, coding style checker, tests, and starting up the project.

```
# Runs PHPUnit
composer test

# Runs php-cs-fixer
composer cs-fixer

# Runs PHPStan
composer phpstan
```

# Changelog

Changelog #1 - Environment
Adding phpmyadmin container for DB management convenience
Adding dev environment as local env
Removed SQLite driver - we should work on prod-like env
Updated app container to run the server

Changelog #2 - DB Schema
Unified database schema to have single source of truth
Added appropriate indexes

Changelog #3 - Composer scripts
Added composer scripts to utilize docker

Changelog #4 - Project quality
Added PHPStan for static analysis
Added PHP CS Fixer for code style enforcement
Created Composer scripts to run PHPStan and CS Fixer via Docker
Fixed code linting issues
Fixed PHPStan standard violations
Fixed ImportService fuel check + test during the process

Changelog #5 - Continuous Integration
Added GitHub Actions workflow triggered on pushes and PRs to main
Split checks into parallel jobs:

PHPUnit tests
PHPStan static analysis
PHP CS Fixer style check
Ensures all PRs pass tests, static analysis, and code style checks before merge

Changelog #6 - UI / UX improvements
Added 2 selection options to UI
- sort timestamp
- filter vehicle selection
Both execute RPC and retrieve processed data

Changelog #7 - Data enrichment
Added API Client
Added MaponClient
Added reusable EnrichmentService 
Added EnrichSingle endpoint
Added EnrichAll endpoint
Added unit tests
Added integration tests

# Code Review

- `src/Rpc/Section/Transaction/Enrich.php:60-62` calls `processSingle()` with the result of `getById()` which can be `null`, causing a TypeError instead of a 404-style response.
- `src/Rpc/Section/Transaction/Enrich.php:16-27` and `src/Rpc/Section/Transaction/EnrichAll.php:16-27` have copy-pasted docblocks that describe list endpoints, not enrichment endpoints. Both files also include unused imports (`DateTime`, `TransactionDTO`) which hints at copy/paste drift.
- `src/Rpc/Section/Transaction/EnrichAll.php:34-44` requires `limit` with no default and throws an error message about `'id'`, which is inconsistent with the parameter name.
- Pagination is inconsistent and misleading. `src/Rpc/Section/Transaction/GetList.php:34-71` accepts `offset`, but `src/Domain/Transaction/Repository/TransactionRepository.php:20-36` ignores it, and `src/Lib/Transaction.php:80-88` has no offset support. This means `offset` is silently ignored and ordering is re-sorted in PHP after the DB query.
- `src/Rpc/Section/Transaction/GetList.php:74-85` sorts in PHP after fetching a limited list, which can return an order different from the true global order if pagination is expected to be consistent.
- `src/Domain/Transaction/Service/ImportService.php:45-86` uses `explode("\n", ...)` + `str_getcsv()` per line, which will break on valid CSV with quoted newlines. It also imports rows one-by-one without a transaction, so partial imports occur on failure.
- `src/Domain/Transaction/Service/ImportService.php:212-278` hardcodes FX rates without date/source and silently falls back to the original amount for unknown currencies; this risks stale or incorrect financial data.
- `src/Lib/ApiClient.php:11-41` contains stale PDO-related comments and an unused `PDO` import. It also sets `Authorization` to `null` and always sends the API key in the query string (`key`) which can leak in logs and is inconsistent with the bearer header.
- `src/Domain/Mapon/MaponClient.php:7-12` includes unused imports (`Transaction`, `Enrich`), indicating drift. `fetchAll()` assumes the API returns a flat list, while `fetchSingle()` expects a nested `data.units` shape, so response handling is inconsistent.
- `src/Domain/Mapon/MaponClient.php:31-35` swallows the original exception details, which reduces observability and makes triage harder.
- `src/Domain/Mapon/MaponUnitData.php:24-28` accesses nested keys without guarding for missing `mileage` or `position`, which can trigger PHP notices or exceptions when API payloads are partial.
- `src/Domain/Mapon/EnrichmentService.php:69-86` docblock says it returns per-transaction results, but it returns only summary counters. This is a documentation/behavior mismatch.
- `src/Lib/Transaction.php:126-138` accepts a `$reason` for `markEnrichmentFailed()`/`markEnrichmentNotFound()` but discards it; callers think they record failure reasons when they do not.
- `src/Rpc/RPC.php:46-99` returns HTTP 400 for all error cases (method not allowed, auth failure, unknown method, etc.). This makes it harder for clients to distinguish between invalid input, auth errors, and missing routes.

# Architectural Observations

- RPC handlers construct infrastructure dependencies inline (API client, Mapon client, repositories) instead of receiving them via composition or a container, hard-coupling transport to concrete implementations. See `src/Rpc/Section/Transaction/Enrich.php` and `src/Rpc/Section/Transaction/EnrichAll.php`.
- Domain services mix orchestration, persistence, and external API concerns in a single flow, blurring responsibilities and complicating retries/transactions. See `src/Domain/Mapon/EnrichmentService.php` and `src/Domain/Transaction/Service/ImportService.php`.
- The repository layer mostly proxies Active Record models, so domain logic still depends on schema and query shapes; the abstraction doesn’t shield persistence concerns. See `src/Domain/Transaction/Repository/TransactionRepository.php`, `src/Lib/Model.php`, and `src/Lib/Transaction.php`.
- RPC routing is convention-based (class name resolution and classmap autoload) rather than explicit route registration, creating implicit coupling between URL shape and class structure. See `src/Rpc/RPC.php` and `composer.json`.
- Cross-cutting concerns like authentication and error mapping are embedded in the RPC dispatcher, coupling protocol handling to business behavior and making consistent error semantics harder to evolve. See `src/Rpc/RPC.php`.
- The Mapon integration assumes different response shapes across methods without a normalized adapter layer, forcing callers to understand API-specific payloads. See `src/Domain/Mapon/MaponClient.php` and `src/Domain/Mapon/MaponUnitData.php`.
- There is no transactional boundary around multi-row imports or enrichment updates, so partial writes are normal and consistency across related records isn’t guaranteed. See `src/Domain/Transaction/Service/ImportService.php` and `src/Domain/Mapon/EnrichmentService.php`.
- Configuration is pulled from `$_ENV` inside request handlers rather than injected, making configuration implicit and difficult to validate at startup. See `src/Rpc/Section/Transaction/Enrich.php`, `src/Rpc/Section/Transaction/EnrichAll.php`, and `src/Lib/DB.php`.

# Frontend Architectural Observations

- The frontend embeds a static API key and performs authentication directly from the browser, which is not a safe trust boundary and tightly couples UI to backend auth strategy. See `public/app.js`.
- The UI relies on RPC method naming conventions and hardcoded method strings, creating a brittle implicit contract that must stay in sync with backend class names. See `public/app.js` and `src/Rpc/RPC.php`.
- There is no shared contract or schema between frontend and backend; the UI assumes response shapes and field names without validation, so API changes are likely to break the UI silently. See `public/app.js`.
- Client-side pagination and filtering are superficial: the UI only filters based on the currently loaded page, and “pagination” only displays counts without navigation. See `public/app.js` and `public/index.html`.
- The frontend is a single script with tightly coupled DOM manipulation and networking logic, making it hard to test or extend (e.g., caching, retries, or error boundaries). See `public/app.js` and `public/index.html`.
