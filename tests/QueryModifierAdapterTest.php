<?php

namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Adapters\QueryModifierAdapter;
use PressGang\Quartermaster\Quartermaster;

final class QueryModifierAdapterTest extends TestCase
{
    // ── Adapter unit tests ──────────────────────────────────────────

    public function testModifySetsScalarArgs(): void
    {
        $query = new \WP_Query();
        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'post_type' => 'product',
            'posts_per_page' => 10,
        ]);

        self::assertSame('product', $query->get('post_type'));
        self::assertSame(10, $query->get('posts_per_page'));
    }

    public function testModifyEmptyArgsDoesNotMutateQuery(): void
    {
        $query = new \WP_Query(['post_type' => 'post']);
        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, []);

        self::assertSame('post', $query->get('post_type'));
    }

    public function testModifyMergesMetaQueryWithExistingClauses(): void
    {
        $query = new \WP_Query([
            'meta_query' => [
                ['key' => 'color', 'value' => 'red', 'compare' => '='],
            ],
        ]);

        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'meta_query' => [
                ['key' => '_price', 'compare' => 'EXISTS'],
                'relation' => 'AND',
            ],
        ]);

        $result = $query->get('meta_query');

        self::assertSame('color', $result[0]['key']);
        self::assertSame('_price', $result[1]['key']);
        self::assertSame('AND', $result['relation']);
    }

    public function testModifyMergesTaxQueryWithExistingClauses(): void
    {
        $query = new \WP_Query([
            'tax_query' => [
                ['taxonomy' => 'category', 'field' => 'slug', 'terms' => ['news'], 'operator' => 'IN'],
            ],
        ]);

        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'tax_query' => [
                ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => ['exclude'], 'operator' => 'NOT IN'],
            ],
        ]);

        $result = $query->get('tax_query');

        self::assertSame('category', $result[0]['taxonomy']);
        self::assertSame('product_visibility', $result[1]['taxonomy']);
        self::assertSame('AND', $result['relation']);
    }

    public function testModifyMergesDateQueryWithExistingClauses(): void
    {
        $query = new \WP_Query([
            'date_query' => [
                ['year' => 2026],
            ],
        ]);

        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'date_query' => [
                ['month' => 6],
            ],
        ]);

        $result = $query->get('date_query');

        self::assertSame(2026, $result[0]['year']);
        self::assertSame(6, $result[1]['month']);
        self::assertSame('AND', $result['relation']);
    }

    public function testModifyAppliesClausesToEmptyExistingQuery(): void
    {
        $query = new \WP_Query();
        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'meta_query' => [
                ['key' => '_price', 'compare' => 'EXISTS'],
            ],
        ]);

        $result = $query->get('meta_query');

        self::assertSame('_price', $result[0]['key']);
        self::assertArrayNotHasKey('relation', $result);
    }

    public function testModifyPreservesExistingRelationOverBuilderDefault(): void
    {
        $query = new \WP_Query([
            'meta_query' => [
                ['key' => 'color', 'value' => 'red', 'compare' => '='],
                ['key' => 'size', 'value' => 'large', 'compare' => '='],
                'relation' => 'OR',
            ],
        ]);

        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'meta_query' => [
                ['key' => '_price', 'compare' => 'EXISTS'],
                'relation' => 'AND',
            ],
        ]);

        $result = $query->get('meta_query');

        // Existing relation (OR) takes precedence since builder's relation is default, not forced
        self::assertSame('OR', $result['relation']);
    }

    public function testModifyHandlesNestedSubGroups(): void
    {
        $query = new \WP_Query([
            'meta_query' => [
                ['key' => 'featured', 'value' => '1', 'compare' => '='],
            ],
        ]);

        $adapter = new QueryModifierAdapter();

        // Simulate what whereMetaNot produces: a nested OR sub-group
        $adapter->modify($query, [
            'meta_query' => [
                [
                    'relation' => 'OR',
                    ['key' => 'hide', 'value' => '1', 'compare' => '!='],
                    ['key' => 'hide', 'compare' => 'NOT EXISTS'],
                ],
                'relation' => 'AND',
            ],
        ]);

        $result = $query->get('meta_query');

        self::assertSame('featured', $result[0]['key']);
        self::assertSame('OR', $result[1]['relation']); // nested sub-group preserved
        self::assertSame('AND', $result['relation']);    // root relation
    }

    public function testModifyMixesScalarAndClauseArgs(): void
    {
        $query = new \WP_Query(['post_type' => 'post']);
        $adapter = new QueryModifierAdapter();

        $adapter->modify($query, [
            'post_type' => 'product',
            'posts_per_page' => 12,
            'meta_query' => [
                ['key' => '_price', 'compare' => 'EXISTS'],
            ],
        ]);

        self::assertSame('product', $query->get('post_type'));
        self::assertSame(12, $query->get('posts_per_page'));

        $meta = $query->get('meta_query');
        self::assertSame('_price', $meta[0]['key']);
    }

    // ── Integration tests via Quartermaster::applyTo() ─────────────

    public function testApplyToSetsScalarArgsOnExistingQuery(): void
    {
        $query = new \WP_Query();

        Quartermaster::posts('product')
            ->status('publish')
            ->limit(12)
            ->applyTo($query);

        self::assertSame('product', $query->get('post_type'));
        self::assertSame('publish', $query->get('post_status'));
        self::assertSame(12, $query->get('posts_per_page'));
    }

    public function testApplyToMergesMetaQueryWithExistingQuery(): void
    {
        $query = new \WP_Query([
            'meta_query' => [
                ['key' => 'featured', 'value' => '1', 'compare' => '='],
            ],
        ]);

        Quartermaster::posts('product')
            ->whereMetaExists('_price')
            ->whereMeta('_price', '', '!=')
            ->applyTo($query);

        $result = $query->get('meta_query');

        self::assertSame('featured', $result[0]['key']);
        self::assertSame('_price', $result[1]['key']);
        self::assertSame('EXISTS', $result[1]['compare']);
        self::assertSame('_price', $result[2]['key']);
        self::assertSame('!=', $result[2]['compare']);
        self::assertSame('AND', $result['relation']);
    }

    public function testApplyToMergesTaxQueryWithExistingQuery(): void
    {
        $query = new \WP_Query([
            'tax_query' => [
                ['taxonomy' => 'category', 'field' => 'slug', 'terms' => ['news'], 'operator' => 'IN'],
            ],
        ]);

        Quartermaster::posts('product')
            ->whereTax('product_visibility', ['exclude-from-catalog'], 'name', 'NOT IN')
            ->applyTo($query);

        $result = $query->get('tax_query');

        self::assertSame('category', $result[0]['taxonomy']);
        self::assertSame('product_visibility', $result[1]['taxonomy']);
        self::assertSame('AND', $result['relation']);
    }

    public function testApplyToIsRecordedInExplain(): void
    {
        $query = new \WP_Query();

        $builder = Quartermaster::posts('product')->status('publish');
        $builder->applyTo($query);

        $explain = $builder->explain();
        $names = array_column($explain['applied'], 'name');

        self::assertContains('applyTo', $names);
    }

    public function testApplyToDoesNotMutateWithEmptyBuilder(): void
    {
        $query = new \WP_Query(['post_type' => 'post', 'posts_per_page' => 5]);

        Quartermaster::prepare()->applyTo($query);

        self::assertSame('post', $query->get('post_type'));
        self::assertSame(5, $query->get('posts_per_page'));
    }

    public function testApplyToFullPreGetPostsScenario(): void
    {
        $query = new \WP_Query([
            'post_type' => 'product',
            'tax_query' => [
                ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => ['clothing'], 'operator' => 'IN'],
            ],
        ]);

        Quartermaster::posts('product')
            ->whereTax('product_visibility', ['exclude-from-catalog', 'exclude-from-search'], 'name', 'NOT IN')
            ->whereMetaExists('_price')
            ->whereMeta('_price', '', '!=')
            ->applyTo($query);

        self::assertSame('product', $query->get('post_type'));

        $tax = $query->get('tax_query');
        self::assertSame('product_cat', $tax[0]['taxonomy']);
        self::assertSame('product_visibility', $tax[1]['taxonomy']);

        $meta = $query->get('meta_query');
        self::assertSame('_price', $meta[0]['key']);
        self::assertSame('EXISTS', $meta[0]['compare']);
        self::assertSame('_price', $meta[1]['key']);
        self::assertSame('!=', $meta[1]['compare']);
    }
}