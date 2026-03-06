<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

use Illuminate\Database\Eloquent\Model;

/**
 * Result class for CRUD operations on models.
 *
 * Use this when a service method creates, updates, or deletes a model
 * and you need to communicate success/failure along with the model.
 */
class OperationResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly ?Model $model = null,
        public readonly array $data = [],
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create a success result for a created model.
     */
    public static function created(Model $model, string $message = 'Created successfully'): static
    {
        return new static(
            success: true,
            message: $message,
            model: $model
        );
    }

    /**
     * Create a success result for an updated model.
     */
    public static function updated(Model $model, string $message = 'Updated successfully'): static
    {
        return new static(
            success: true,
            message: $message,
            model: $model
        );
    }

    /**
     * Create a success result for a deleted model.
     */
    public static function deleted(string $message = 'Deleted successfully'): static
    {
        return new static(
            success: true,
            message: $message
        );
    }

    /**
     * Create a success result with a model.
     */
    public static function success(?Model $model = null, string $message = ''): static
    {
        return new static(
            success: true,
            message: $message,
            model: $model
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message, ?string $errorCode = null, array $data = []): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: $errorCode,
            data: $data
        );
    }

    /**
     * Create a not found failure result.
     */
    public static function notFound(string $message = 'Resource not found'): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: 'NOT_FOUND'
        );
    }

    /**
     * Create a validation failure result.
     */
    public static function validationFailed(string $message, array $errors = []): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: 'VALIDATION_FAILED',
            data: ['errors' => $errors]
        );
    }

    /**
     * Create an unauthorized failure result.
     */
    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: 'UNAUTHORIZED'
        );
    }

    /**
     * Get the model from the result.
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }

    /**
     * Get additional data from the result.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Return a new result with additional data.
     */
    public function withData(array $data): static
    {
        return new static(
            success: $this->success,
            message: $this->message,
            errorCode: $this->errorCode,
            model: $this->model,
            data: array_merge($this->data, $data)
        );
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ($this->model !== null) {
            $array['model'] = $this->model->toArray();
        }

        if (! empty($this->data)) {
            $array['data'] = $this->data;
        }

        return $array;
    }
}
