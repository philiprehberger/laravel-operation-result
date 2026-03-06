<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

use Illuminate\Support\Collection;

/**
 * Result class for collection/list operations.
 *
 * Use this when a service method returns a collection of items
 * with optional pagination metadata.
 */
class CollectionResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly Collection|array $items = [],
        public readonly ?int $total = null,
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?bool $hasMore = null,
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create a success result with items.
     */
    public static function withItems(
        Collection|array $items,
        ?int $total = null,
        string $message = ''
    ): static {
        return new static(
            success: true,
            message: $message,
            items: $items,
            total: $total
        );
    }

    /**
     * Create a success result with paginated items.
     */
    public static function paginated(
        Collection|array $items,
        int $total,
        int $page,
        int $perPage,
        string $message = ''
    ): static {
        $hasMore = ($page * $perPage) < $total;

        return new static(
            success: true,
            message: $message,
            items: $items,
            total: $total,
            page: $page,
            perPage: $perPage,
            hasMore: $hasMore
        );
    }

    /**
     * Create an empty success result.
     */
    public static function empty(string $message = 'No items found'): static
    {
        return new static(
            success: true,
            message: $message,
            items: [],
            total: 0
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message, ?string $errorCode = null): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: $errorCode
        );
    }

    /**
     * Get the items from the result.
     */
    public function getItems(): Collection|array
    {
        return $this->items;
    }

    /**
     * Get the total count.
     */
    public function getTotal(): ?int
    {
        return $this->total;
    }

    /**
     * Get the item count.
     */
    public function count(): int
    {
        return $this->items instanceof Collection
            ? $this->items->count()
            : count($this->items);
    }

    /**
     * Check if there are any items.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Check if there are more items available.
     */
    public function hasMore(): bool
    {
        return $this->hasMore ?? false;
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['items'] = $this->items instanceof Collection
            ? $this->items->toArray()
            : $this->items;

        $array['count'] = $this->count();

        if ($this->total !== null) {
            $array['total'] = $this->total;
        }

        if ($this->page !== null) {
            $array['page'] = $this->page;
            $array['per_page'] = $this->perPage;
            $array['has_more'] = $this->hasMore;
        }

        return $array;
    }
}
