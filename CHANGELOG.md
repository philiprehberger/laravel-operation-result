# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.3] - 2026-03-17

### Fixed
- Add phpstan.neon configuration for CI static analysis

## [1.0.2] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.0.1] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts
- Add Development section to README

## [1.0.0] - 2026-03-05

### Added

- `ResultContract` interface defining the `succeeded()`, `failed()`, `getMessage()`, and `toArray()` contract.
- Abstract `Result` base class with `success`, `message`, and `errorCode` properties and `getErrorCode()` method.
- `OperationResult` for CRUD operations on Eloquent models. Factory methods: `created`, `updated`, `deleted`, `success`, `failure`, `notFound`, `validationFailed`, `unauthorized`. Fluent `withData()` method.
- `BulkActionResult` for operations on multiple items. Factory methods: `success`, `partial`, `failure`. Helper methods: `hasFailures()`, `isComplete()`, `getFailedIds()`, `getSuccessIds()`, `canUndo()`.
- `CollectionResult` for collection/list service returns with optional pagination. Factory methods: `withItems`, `paginated`, `empty`, `failure`. Helper methods: `getItems()`, `getTotal()`, `count()`, `isEmpty()`, `hasMore()`.
- `ValidationResult` for validation operations tracking both errors and warnings. Factory methods: `valid`, `invalid`, `failure`. Helper methods: `isValid()`, `getErrors()`, `getWarnings()`, `hasErrors()`, `hasWarnings()`.
- `RateLimitResult` for API rate limit enforcement. Factory methods: `allowed`, `denied`. Helper methods: `isAllowed()`, `isDenied()`, `getHeaders()` for HTTP header generation.
- `UndoResult` for undo operations tracking restored vs failed items. Factory methods: `success`, `partial`, `failure`. Helper method: `hasFailures()`.
- Full PHPUnit 11 test suite with Orchestra Testbench for Eloquent-dependent tests.
- GitHub Actions CI workflow for PHP 8.2, 8.3, and 8.4.
