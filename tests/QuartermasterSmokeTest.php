<?php


namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Quartermaster;

final class QuartermasterSmokeTest extends TestCase
{
    public function testPostTypeIsSetInArgs(): void
    {
        $args = Quartermaster::prepare()->postType('post')->toArgs();

        self::assertArrayHasKey('post_type', $args);
        self::assertSame('post', $args['post_type']);
    }

    public function testBlankSearchDoesNotSetSearchArg(): void
    {
        $args = Quartermaster::prepare()->search('   ')->toArgs();

        self::assertArrayNotHasKey('s', $args);
    }

    public function testWhereMetaIsFluentAndSetsMetaQuery(): void
    {
        $builder = Quartermaster::prepare()->whereMeta('start', '2026-01-01', '>=', 'DATE');
        $args = $builder->toArgs();

        self::assertInstanceOf(Quartermaster::class, $builder);
        self::assertArrayHasKey('meta_query', $args);
        self::assertSame('AND', $args['meta_query']['relation']);
    }

    public function testOrWhereMetaIsFluentAndSetsMetaQueryRelation(): void
    {
        $builder = Quartermaster::prepare()->orWhereMeta('start', '2026-01-01', '>=', 'DATE');
        $args = $builder->toArgs();

        self::assertInstanceOf(Quartermaster::class, $builder);
        self::assertArrayHasKey('meta_query', $args);
        self::assertSame('OR', $args['meta_query']['relation']);
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
