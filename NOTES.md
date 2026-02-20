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

