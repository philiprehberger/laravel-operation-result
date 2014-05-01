<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\OperationResult\OperationResult;

class OperationResultTest extends TestCase
{
    private function makeModel(): Model
    {
        return new class extends Model
        {
            protected $table = 'users';

            protected $fillable = ['id', 'name', 'email'];

            public function toArray(): array
            {
                return ['id' => 1, 'name' => 'Test User', 'email' => 'test@example.com'];
            }
        };
    }

    public function test_created_returns_success_result_with_model(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::created($model);

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertSame('Created successfully', $result->getMessage());
        $this->assertSame($model, $result->getModel());
        $this->assertNull($result->getErrorCode());
    }

    public function test_created_accepts_custom_message(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::created($model, 'Client created.');

        $this->assertSame('Client created.', $result->getMessage());
    }

    public function test_updated_returns_success_result_with_model(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::updated($model);

        $this->assertTrue($result->succeeded());
        $this->assertSame('Updated successfully', $result->getMessage());
        $this->assertSame($model, $result->getModel());
    }

    public function test_updated_accepts_custom_message(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::updated($model, 'Invoice updated.');

        $this->assertSame('Invoice updated.', $result->getMessage());
    }

    public function test_deleted_returns_success_result_without_model(): void
    {
        $result = OperationResult::deleted();

        $this->assertTrue($result->succeeded());
        $this->assertSame('Deleted successfully', $result->getMessage());
        $this->assertNull($result->getModel());
    }

    public function test_deleted_accepts_custom_message(): void
    {
        $result = OperationResult::deleted('Project removed.');

        $this->assertSame('Project removed.', $result->getMessage());
    }

    public function test_success_returns_success_result(): void
    {
        $result = OperationResult::success();

        $this->assertTrue($result->succeeded());
        $this->assertNull($result->getModel());
        $this->assertSame('', $result->getMessage());
    }

    public function test_success_with_model(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::success($model, 'Done.');

        $this->assertTrue($result->succeeded());
        $this->assertSame($model, $result->getModel());
        $this->assertSame('Done.', $result->getMessage());
    }

    public function test_failure_returns_failed_result(): void
    {
        $result = OperationResult::failure('Something went wrong');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame('Something went wrong', $result->getMessage());
        $this->assertNull($result->getModel());
    }

    public function test_failure_with_error_code_and_data(): void
    {
        $result = OperationResult::failure('DB error', 'DB_ERROR', ['query' => 'SELECT *']);

        $this->assertSame('DB_ERROR', $result->getErrorCode());
        $this->assertSame(['query' => 'SELECT *'], $result->getData());
    }

    public function test_not_found_returns_correct_error_code(): void
    {
        $result = OperationResult::notFound();

        $this->assertFalse($result->succeeded());
        $this->assertSame('Resource not found', $result->getMessage());
        $this->assertSame('NOT_FOUND', $result->getErrorCode());
    }

    public function test_not_found_accepts_custom_message(): void
    {
        $result = OperationResult::notFound('Invoice not found');

        $this->assertSame('Invoice not found', $result->getMessage());
    }

    public function test_validation_failed_sets_correct_error_code_and_errors(): void
    {
        $errors = ['name' => ['The name field is required.']];
        $result = OperationResult::validationFailed('Validation failed', $errors);

        $this->assertFalse($result->succeeded());
        $this->assertSame('VALIDATION_FAILED', $result->getErrorCode());
        $this->assertSame(['errors' => $errors], $result->getData());
    }

    public function test_unauthorized_returns_correct_error_code(): void
    {
        $result = OperationResult::unauthorized();

        $this->assertFalse($result->succeeded());
        $this->assertSame('Unauthorized', $result->getMessage());
        $this->assertSame('UNAUTHORIZED', $result->getErrorCode());
    }

    public function test_unauthorized_accepts_custom_message(): void
    {
        $result = OperationResult::unauthorized('Access denied to this project.');

        $this->assertSame('Access denied to this project.', $result->getMessage());
    }

    public function test_with_data_merges_data_onto_result(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::created($model)->withData(['redirect' => '/dashboard']);

        $this->assertTrue($result->succeeded());
        $this->assertSame(['redirect' => '/dashboard'], $result->getData());
        $this->assertSame($model, $result->getModel());
    }

    public function test_with_data_merges_with_existing_data(): void
    {
        $result = OperationResult::failure('Err', 'ERR', ['a' => 1])->withData(['b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $result->getData());
    }

    public function test_to_array_success_without_model(): void
    {
        $result = OperationResult::deleted('Gone.');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('Gone.', $array['message']);
        $this->assertArrayNotHasKey('model', $array);
        $this->assertArrayNotHasKey('data', $array);
    }

    public function test_to_array_includes_model_when_present(): void
    {
        $model = $this->makeModel();
        $result = OperationResult::created($model);
        $array = $result->toArray();

        $this->assertArrayHasKey('model', $array);
        $this->assertSame($model->toArray(), $array['model']);
    }

    public function test_to_array_includes_data_when_present(): void
    {
        $result = OperationResult::failure('Err', 'ERR', ['details' => 'bad input']);
        $array = $result->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertSame(['details' => 'bad input'], $array['data']);
    }

    public function test_to_array_includes_error_code_when_set(): void
    {
        $result = OperationResult::notFound();
        $array = $result->toArray();

        $this->assertArrayHasKey('error_code', $array);
        $this->assertSame('NOT_FOUND', $array['error_code']);
    }

    public function test_get_data_returns_empty_array_by_default(): void
    {
        $result = OperationResult::deleted();

        $this->assertSame([], $result->getData());
    }
}
