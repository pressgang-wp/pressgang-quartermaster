<?php


namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Quartermaster;

final class QuartermasterSmokeTest extends TestCase
{
    public function testPostTypeIsFluent(): void
    {
        $builder = Quartermaster::prepare()->postType('post');

        self::assertInstanceOf(Quartermaster::class, $builder);
    }

    public function testWhereMetaIsFluent(): void
    {
        $builder = Quartermaster::prepare()->whereMeta('start', '2026-01-01', '>=', 'DATE');

        self::assertInstanceOf(Quartermaster::class, $builder);
    }

    public function testOrWhereMetaIsFluent(): void
    {
        $builder = Quartermaster::prepare()->orWhereMeta('start', '2026-01-01', '>=', 'DATE');

        self::assertInstanceOf(Quartermaster::class, $builder);
    }

    public function testWhereTaxIsFluent(): void
    {
        $builder = Quartermaster::prepare()->whereTax('topic', ['news']);

        self::assertInstanceOf(Quartermaster::class, $builder);
    }

    public function testPostTypeSetsPostTypeArg(): void
    {
        $args = Quartermaster::prepare()->postType('post')->toArgs();

        self::assertArrayHasKey('post_type', $args);
        self::assertSame('post', $args['post_type']);
    }

    public function testStatusSetsPostStatusArg(): void
    {
        $args = Quartermaster::prepare()->status('publish')->toArgs();

        self::assertArrayHasKey('post_status', $args);
        self::assertSame('publish', $args['post_status']);
    }

    public function testBlankSearchDoesNotSetSearchArg(): void
    {
        $args = Quartermaster::prepare()->search('   ')->toArgs();

        self::assertArrayNotHasKey('s', $args);
    }

    public function testWhereMetaCreatesMetaQueryArray(): void
    {
        $args = Quartermaster::prepare()->whereMeta('start', '2026-01-01', '>=', 'DATE')->toArgs();

        self::assertArrayHasKey('meta_query', $args);
        self::assertIsArray($args['meta_query']);
        self::assertArrayHasKey(0, $args['meta_query']);
        self::assertSame('start', $args['meta_query'][0]['key']);
    }

    public function testOrWhereMetaSetsRootRelationToOrWhenMultipleClauses(): void
    {
        $args = Quartermaster::prepare()
            ->whereMeta('start', '2026-01-01', '>=', 'DATE')
            ->orWhereMeta('featured', '1')
            ->toArgs();

        self::assertSame('OR', $args['meta_query']['relation']);
    }

    public function testWhereTaxCreatesTaxQueryArray(): void
    {
        $args = Quartermaster::prepare()->whereTax('topic', ['news', 'events'])->toArgs();

        self::assertArrayHasKey('tax_query', $args);
        self::assertIsArray($args['tax_query']);
        self::assertArrayHasKey(0, $args['tax_query']);
        self::assertSame('topic', $args['tax_query'][0]['taxonomy']);
    }

    public function testPagedCanBeProvidedWithoutWordPressRuntime(): void
    {
        $args = Quartermaster::prepare()->paged(12, 0)->toArgs();

        self::assertSame(12, $args['posts_per_page']);
        self::assertSame(1, $args['paged']);
    }

    public function testExplainIncludesWarnings(): void
    {
        $explain = Quartermaster::prepare()->orderBy('meta_value')->explain();

        self::assertArrayHasKey('warnings', $explain);
        self::assertNotEmpty($explain['warnings']);
    }

    public function testWpQueryCanBeSkippedWithoutWordPress(): void
    {
        if (!class_exists('WP_Query')) {
            self::markTestSkipped('WordPress is not bootstrapped.');
        }

        $result = Quartermaster::prepare()->postType('post')->wpQuery();

        self::assertInstanceOf('WP_Query', $result);
    }

    public function testTimberCanBeSkippedWhenUnavailable(): void
    {
        if (!class_exists(\Timber\PostQuery::class)) {
            self::markTestSkipped('Timber is unavailable in this environment.');
        }

        $result = Quartermaster::prepare()->postType('post')->timber();

        self::assertIsObject($result);
    }
}
