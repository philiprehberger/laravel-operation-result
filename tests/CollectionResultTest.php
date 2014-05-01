<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\OperationResult\CollectionResult;

class CollectionResultTest extends TestCase
{
    public function test_with_items_from_array_returns_success(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $result = CollectionResult::withItems($items);

        $this->assertTrue($result->succeeded());
        $this->assertSame($items, $result->getItems());
        $this->assertNull($result->getTotal());
    }

    public function test_with_items_from_collection_returns_success(): void
    {
        $items = collect([['id' => 1], ['id' => 2], ['id' => 3]]);
        $result = CollectionResult::withItems($items, 3, 'Projects loaded.');

        $this->assertTrue($result->succeeded());
        $this->assertInstanceOf(Collection::class, $result->getItems());
        $this->assertSame(3, $result->getTotal());
        $this->assertSame('Projects loaded.', $result->getMessage());
    }

    public function test_paginated_calculates_has_more_correctly(): void
    {
        $items = collect([1, 2, 3, 4, 5]);
        $result = CollectionResult::paginated($items, total: 15, page: 1, perPage: 5);

        $this->assertTrue($result->succeeded());
        $this->assertSame(15, $result->getTotal());
        $this->assertTrue($result->hasMore());
    }

    public function test_paginated_has_more_false_on_last_page(): void
    {
        $items = collect([11, 12, 13]);
        $result = CollectionResult::paginated($items, total: 13, page: 3, perPage: 5);

        $this->assertFalse($result->hasMore());
    }

    public function test_paginated_stores_page_and_per_page(): void
    {
        $items = [1, 2];
        $result = CollectionResult::paginated($items, total: 20, page: 2, perPage: 10);

        $this->assertSame(2, $result->page);
        $this->assertSame(10, $result->perPage);
    }

    public function test_empty_returns_success_with_zero_total(): void
    {
        $result = CollectionResult::empty();

        $this->assertTrue($result->succeeded());
        $this->assertSame(0, $result->getTotal());
        $this->assertSame('No items found', $result->getMessage());
        $this->assertTrue($result->isEmpty());
    }

    public function test_empty_accepts_custom_message(): void
    {
        $result = CollectionResult::empty('No clients yet.');

        $this->assertSame('No clients yet.', $result->getMessage());
    }

    public function test_failure_returns_failed_result(): void
    {
        $result = CollectionResult::failure('Could not load clients', 'DB_ERROR');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame('Could not load clients', $result->getMessage());
        $this->assertSame('DB_ERROR', $result->getErrorCode());
    }

    public function test_get_items_returns_items(): void
    {
        $items = ['a', 'b', 'c'];
        $result = CollectionResult::withItems($items);

        $this->assertSame($items, $result->getItems());
    }

    public function test_get_total_returns_null_when_not_set(): void
    {
        $result = CollectionResult::withItems([]);

        $this->assertNull($result->getTotal());
    }

    public function test_count_with_array(): void
    {
        $result = CollectionResult::withItems([1, 2, 3]);

        $this->assertSame(3, $result->count());
    }

    public function test_count_with_collection(): void
    {
        $result = CollectionResult::withItems(collect([1, 2, 3, 4]));

        $this->assertSame(4, $result->count());
    }

    public function test_is_empty_true_for_empty_array(): void
    {
        $result = CollectionResult::withItems([]);

        $this->assertTrue($result->isEmpty());
    }

    public function test_is_empty_false_when_items_present(): void
    {
        $result = CollectionResult::withItems([1]);

        $this->assertFalse($result->isEmpty());
    }

    public function test_has_more_defaults_to_false_when_null(): void
    {
        $result = CollectionResult::withItems([1, 2]);

        $this->assertFalse($result->hasMore());
    }

    public function test_to_array_for_plain_list(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $result = CollectionResult::withItems($items, 2);
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame($items, $array['items']);
        $this->assertSame(2, $array['count']);
        $this->assertSame(2, $array['total']);
        $this->assertArrayNotHasKey('page', $array);
        $this->assertArrayNotHasKey('per_page', $array);
        $this->assertArrayNotHasKey('has_more', $array);
    }

    public function test_to_array_for_paginated_result(): void
    {
        $items = collect([1, 2, 3]);
        $result = CollectionResult::paginated($items, total: 30, page: 2, perPage: 10);
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame([1, 2, 3], $array['items']);
        $this->assertSame(3, $array['count']);
        $this->assertSame(30, $array['total']);
        $this->assertSame(2, $array['page']);
        $this->assertSame(10, $array['per_page']);
        $this->assertTrue($array['has_more']);
    }

    public function test_to_array_omits_total_when_not_set(): void
    {
        $result = CollectionResult::withItems([]);
        $array = $result->toArray();

        $this->assertArrayNotHasKey('total', $array);
    }
}
