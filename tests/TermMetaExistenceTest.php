<?php

namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Quartermaster;

final class TermMetaExistenceTest extends TestCase
{
    public function test_existence_clauses_are_valueless_and_compose_with_filters(): void
    {
        $query = Quartermaster::terms('category')
            ->whereMetaExists('image')
            ->whereMeta('image', '', '!=')
            ->whereMetaNotExists('hidden');

        $meta = $query->toArgs()['meta_query'];
        self::assertSame('AND', $meta['relation']);
        self::assertSame(['key' => 'image', 'compare' => 'EXISTS'], $meta[0]);
        self::assertSame('!=', $meta[1]['compare']);
        self::assertSame(['key' => 'hidden', 'compare' => 'NOT EXISTS'], $meta[2]);
        self::assertSame(
            ['taxonomy', 'whereMetaExists', 'whereMeta', 'whereMetaNotExists'],
            array_column($query->explain()['applied'], 'name')
        );
    }
}
