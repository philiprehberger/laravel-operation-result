# Laravel Operation Result

[![Tests](https://github.com/philiprehberger/laravel-operation-result/actions/workflows/tests.yml/badge.svg)](https://github.com/philiprehberger/laravel-operation-result/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/philiprehberger/laravel-operation-result.svg)](https://packagist.org/packages/philiprehberger/laravel-operation-result)
[![License](https://img.shields.io/github/license/philiprehberger/laravel-operation-result)](LICENSE)

A typed Result pattern for Laravel service-layer operations. Each service method returns a strongly-typed result object instead of throwing exceptions for predictable failures — making controllers simpler and business logic easier to test.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-operation-result
```

No service provider registration is needed. The classes are ready to use immediately.

## Why Use This?

Without a result pattern, service methods either throw exceptions for every failure or return ambiguous booleans/nulls that force controllers to guess what went wrong. Result objects make the contract explicit:

```php
// Without result objects
public function createClient(array $data): Client
{
    // Throws on validation, throws on DB error, throws on auth — controller catches them all
}

// With result objects
public function createClient(array $data): OperationResult
{
    // Returns a structured result — controller knows exactly what to check
}
```

## Available Result Types

| Class | Use Case |
|---|---|
| `OperationResult` | Model CRUD operations (create, update, delete) |
| `BulkActionResult` | Operations on multiple items at once |
| `CollectionResult` | Service methods returning lists or paginated data |
| `ValidationResult` | Data and template validation with errors and warnings |
| `RateLimitResult` | API rate limit checks with HTTP header generation |
| `UndoResult` | Undo operations tracking restored vs failed items |

All classes implement `ResultContract` and extend the abstract `Result` base class.

---

## OperationResult

Use when a service method creates, reads, updates, or deletes an Eloquent model.

### Service

```php
use PhilipRehberger\OperationResult\OperationResult;

class ClientService
{
    public function create(array $data): OperationResult
    {
        if (!auth()->user()->can('create', Client::class)) {
            return OperationResult::unauthorized();
        }

        $validator = Validator::make($data, ['name' => 'required|string|max:255']);

        if ($validator->fails()) {
            return OperationResult::validationFailed('Validation failed', $validator->errors()->toArray());
        }

        $client = Client::create($data);

        return OperationResult::created($client);
    }

    public function update(Client $client, array $data): OperationResult
    {
        $client->update($data);

        return OperationResult::updated($client, 'Client profile updated.');
    }

    public function delete(int $id): OperationResult
    {
        $client = Client::find($id);

        if (!$client) {
            return OperationResult::notFound('Client not found.');
        }

        $client->delete();

        return OperationResult::deleted();
    }
}
```

### Controller

```php
public function store(StoreClientRequest $request, ClientService $service): JsonResponse
{
    $result = $service->create($request->validated());

    if ($result->failed()) {
        return response()->json($result->toArray(), match ($result->getErrorCode()) {
            'UNAUTHORIZED'       => 403,
            'VALIDATION_FAILED'  => 422,
            default              => 500,
        });
    }

    return response()->json($result->toArray(), 201);
}
```

### Factory Methods

| Method | Description |
|---|---|
| `OperationResult::created($model, $message)` | Success — model was created |
| `OperationResult::updated($model, $message)` | Success — model was updated |
| `OperationResult::deleted($message)` | Success — model was deleted |
| `OperationResult::success($model, $message)` | Generic success, model optional |
| `OperationResult::failure($message, $errorCode, $data)` | Generic failure |
| `OperationResult::notFound($message)` | 404-style failure, error code `NOT_FOUND` |
| `OperationResult::validationFailed($message, $errors)` | Validation failure, error code `VALIDATION_FAILED` |
| `OperationResult::unauthorized($message)` | Auth failure, error code `UNAUTHORIZED` |

### Additional Methods

```php
$result->getModel();         // ?Model
$result->getData();          // array
$result->withData(['key' => 'value']); // returns new instance with merged data
$result->getErrorCode();     // ?string
$result->toArray();          // array
```

---

## BulkActionResult

Use when operating on multiple items at once, such as bulk-deleting, bulk-archiving, or bulk-updating a set of records.

### Service

```php
use PhilipRehberger\OperationResult\BulkActionResult;

class BulkClientService
{
    public function archiveMany(array $ids): BulkActionResult
    {
        $processed = 0;
        $details = [];

        foreach ($ids as $id) {
            $client = Client::find($id);

            if (!$client) {
                $details[] = ['id' => $id, 'success' => false, 'error' => 'Not found'];
                continue;
            }

            $client->update(['status' => 'archived']);
            $details[] = ['id' => $id, 'success' => true];
            $processed++;
        }

        $failed = count($ids) - $processed;

        if ($failed > 0 && $processed > 0) {
            return BulkActionResult::partial($processed, $failed, "{$processed} archived, {$failed} failed.", $details);
        }

        if ($failed > 0) {
            return BulkActionResult::failure('No clients were archived.', null, $details);
        }

        $undoToken = Str::uuid()->toString();
        Cache::put("undo:{$undoToken}", $ids, now()->addMinutes(10));

        return BulkActionResult::success($processed, "{$processed} clients archived.", $details, $undoToken);
    }
}
```

### Controller

```php
public function bulkArchive(BulkArchiveRequest $request, BulkClientService $service): JsonResponse
{
    $result = $service->archiveMany($request->input('ids'));

    $status = $result->succeeded() ? 200 : 422;

    return response()->json($result->toArray(), $status);
}
```

### Factory Methods

| Method | Description |
|---|---|
| `BulkActionResult::success($processed, $message, $details, $undoToken, $undoExpiresAt)` | All items processed |
| `BulkActionResult::partial($processed, $failed, $message, $details, $undoToken, $undoExpiresAt)` | Mixed results |
| `BulkActionResult::failure($message, $errorCode, $details)` | Complete failure |

### Additional Methods

```php
$result->hasFailures();     // bool — true if any items failed
$result->isComplete();      // bool — true if processed > 0 and failed === 0
$result->getFailedIds();    // array — IDs from details where success === false
$result->getSuccessIds();   // array — IDs from details where success === true
$result->canUndo();         // bool — true if undoToken is set
```

---

## CollectionResult

Use when a service method returns a list of items, with or without pagination.

### Service

```php
use PhilipRehberger\OperationResult\CollectionResult;

class ProjectService
{
    public function listForClient(int $clientId, int $page = 1, int $perPage = 15): CollectionResult
    {
        $paginator = Project::where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($paginator->isEmpty()) {
            return CollectionResult::empty('No projects found for this client.');
        }

        return CollectionResult::paginated(
            $paginator->getCollection(),
            total: $paginator->total(),
            page: $page,
            perPage: $perPage
        );
    }

    public function getRecent(): CollectionResult
    {
        try {
            $projects = Project::orderByDesc('updated_at')->limit(10)->get();

            return CollectionResult::withItems($projects, $projects->count());
        } catch (\Exception $e) {
            return CollectionResult::failure('Could not load projects.', 'DB_ERROR');
        }
    }
}
```

### Controller

```php
public function index(Request $request, ProjectService $service): JsonResponse
{
    $result = $service->listForClient(
        $request->user()->client_id,
        $request->integer('page', 1)
    );

    if ($result->failed()) {
        return response()->json(['error' => $result->getMessage()], 500);
    }

    return response()->json($result->toArray());
}
```

### Factory Methods

| Method | Description |
|---|---|
| `CollectionResult::withItems($items, $total, $message)` | Success with a list, no pagination |
| `CollectionResult::paginated($items, $total, $page, $perPage, $message)` | Success with pagination metadata |
| `CollectionResult::empty($message)` | Success with zero items |
| `CollectionResult::failure($message, $errorCode)` | Failure |

### Additional Methods

```php
$result->getItems();   // Collection|array
$result->getTotal();   // ?int
$result->count();      // int — count of items in this result
$result->isEmpty();    // bool
$result->hasMore();    // bool — true when more pages exist
```

---

## ValidationResult

Use when a service or class validates data, tracking both hard errors (blocking) and soft warnings (advisory).

### Service

```php
use PhilipRehberger\OperationResult\ValidationResult;

class InvoiceTemplateValidator
{
    public function validate(array $templateData): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if (empty($templateData['line_items'])) {
            $errors['line_items'] = 'At least one line item is required.';
        }

        if (!isset($templateData['due_date'])) {
            $warnings['due_date'] = 'No due date set; invoice will have no payment deadline.';
        }

        if (!empty($errors)) {
            return ValidationResult::invalid($errors, $warnings);
        }

        return ValidationResult::valid($warnings);
    }
}
```

### Controller

```php
public function validateTemplate(Request $request, InvoiceTemplateValidator $validator): JsonResponse
{
    $result = $validator->validate($request->all());

    $status = $result->isValid() ? 200 : 422;

    return response()->json($result->toArray(), $status);
}
```

### Factory Methods

| Method | Description |
|---|---|
| `ValidationResult::valid($warnings)` | Passes, optional warnings |
| `ValidationResult::invalid($errors, $warnings)` | Fails with errors, optional warnings |
| `ValidationResult::failure($message, $errorCode)` | Unexpected failure (not a validation error) |

### Additional Methods

```php
$result->isValid();       // bool
$result->hasErrors();     // bool
$result->hasWarnings();   // bool
$result->getErrors();     // array
$result->getWarnings();   // array
```

---

## RateLimitResult

Use when checking or enforcing API rate limits. Provides typed results and generates standard HTTP rate-limit response headers.

### Service

```php
use PhilipRehberger\OperationResult\RateLimitResult;

class ApiRateLimiter
{
    public function check(string $apiKey, string $scope): RateLimitResult
    {
        $limit = 1000;
        $window = 3600; // 1 hour
        $cacheKey = "rate_limit:{$apiKey}:{$scope}";
        $resetAt = now()->addHour()->timestamp;

        $current = Cache::increment($cacheKey);

        if ($current === 1) {
            Cache::expire($cacheKey, $window);
        }

        $remaining = max(0, $limit - $current);

        if ($current > $limit) {
            $ttl = Cache::ttl($cacheKey);
            return RateLimitResult::denied($limit, $resetAt, $ttl);
        }

        return RateLimitResult::allowed($limit, $remaining, $resetAt);
    }
}
```

### Middleware

```php
public function handle(Request $request, Closure $next): Response
{
    $result = $this->rateLimiter->check($request->header('X-API-Key'), 'default');

    $response = $result->isDenied()
        ? response()->json(['error' => $result->getMessage()], 429)
        : $next($request);

    foreach ($result->getHeaders() as $header => $value) {
        $response->headers->set($header, $value);
    }

    return $response;
}
```

### Factory Methods

| Method | Description |
|---|---|
| `RateLimitResult::allowed($limit, $remaining, $resetAt)` | Request is within limit |
| `RateLimitResult::denied($limit, $resetAt, $retryAfter)` | Limit exceeded, error code `RATE_LIMITED` |

### Additional Methods

```php
$result->isAllowed();   // bool
$result->isDenied();    // bool
$result->getHeaders();  // array<string, string> — X-RateLimit-* headers, plus Retry-After when denied
```

---

## UndoResult

Use when reversing a previous bulk operation, tracking how many items were restored successfully vs how many failed.

### Service

```php
use PhilipRehberger\OperationResult\UndoResult;

class UndoService
{
    public function undo(string $token): UndoResult
    {
        $ids = Cache::pull("undo:{$token}");

        if (!$ids) {
            return UndoResult::failure('Undo token not found or has expired.', 'TOKEN_EXPIRED');
        }

        $restored = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $client = Client::withTrashed()->find($id);

            if ($client && $client->restore()) {
                $restored++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0 && $restored > 0) {
            return UndoResult::partial($restored, $failed, "{$restored} clients restored, {$failed} could not be undone.");
        }

        if ($failed > 0) {
            return UndoResult::failure('Undo failed for all items.');
        }

        return UndoResult::success($restored);
    }
}
```

### Controller

```php
public function undo(string $token, UndoService $service): JsonResponse
{
    $result = $service->undo($token);

    $status = $result->succeeded() ? 200 : 422;

    return response()->json($result->toArray(), $status);
}
```

### Factory Methods

| Method | Description |
|---|---|
| `UndoResult::success($restored, $message)` | All items restored |
| `UndoResult::partial($restored, $failed, $message)` | Mixed results |
| `UndoResult::failure($message, $errorCode)` | Complete failure |

### Additional Methods

```php
$result->hasFailures();  // bool — true if any items could not be restored
```

---

## The ResultContract Interface

All result types implement `PhilipRehberger\OperationResult\Contracts\ResultContract`:

```php
interface ResultContract
{
    public function succeeded(): bool;
    public function failed(): bool;
    public function getMessage(): string;
    public function toArray(): array;
}
```

Use this interface for type hints when you accept any result type:

```php
public function logResult(ResultContract $result): void
{
    Log::info($result->getMessage(), $result->toArray());
}
```

---

## API

| Class | Use Case |
|-------|---------|
| `OperationResult` | Model CRUD operations (create, update, delete) |
| `BulkActionResult` | Operations on multiple items at once |
| `CollectionResult` | Service methods returning lists or paginated data |
| `ValidationResult` | Data and template validation with errors and warnings |
| `RateLimitResult` | API rate limit checks with HTTP header generation |
| `UndoResult` | Undo operations tracking restored vs failed items |

All classes implement `ResultContract`: `succeeded()`, `failed()`, `getMessage()`, `toArray()`.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT

