<?php

/**
 * Lightweight stubs for WP_Query and Timber\PostQuery.
 *
 * These match the Timber 2 constructor signature so the adapter
 * wiring can be tested without bootstrapping WordPress or Timber.
 */

namespace {
    if (!class_exists('WP_Query')) {
        class WP_Query
        {
            public array $query_vars;

            public function __construct(array $args = [])
            {
                $this->query_vars = $args;
            }
        }
    }
}

namespace Timber {
    if (!class_exists(\Timber\PostQuery::class)) {
        class PostQuery
        {
            public \WP_Query $query;

            public function __construct(\WP_Query $query)
            {
                $this->query = $query;
            }
        }
    }
}

namespace PressGang\Quartermaster\Tests {

    use PHPUnit\Framework\TestCase;
    use PressGang\Quartermaster\Adapters\TimberAdapter;
    use PressGang\Quartermaster\Adapters\WpAdapter;

    final class AdapterTest extends TestCase
    {
        public function testWpAdapterReturnsWpQueryInstance(): void
        {
            $result = (new WpAdapter())->wpQuery(['post_type' => 'post']);

            self::assertInstanceOf(\WP_Query::class, $result);
        }

        public function testWpAdapterPassesArgsToWpQuery(): void
        {
            $result = (new WpAdapter())->wpQuery(['post_type' => 'post', 'posts_per_page' => 5]);

            self::assertSame(['post_type' => 'post', 'posts_per_page' => 5], $result->query_vars);
        }

        public function testTimberAdapterReturnsPostQueryInstance(): void
        {
            $result = (new TimberAdapter())->postQuery(['post_type' => 'post']);

            self::assertInstanceOf(\Timber\PostQuery::class, $result);
        }

        public function testTimberAdapterPassesWpQueryInstanceToPostQuery(): void
        {
            $result = (new TimberAdapter())->postQuery(['post_type' => 'post']);

            self::assertInstanceOf(\WP_Query::class, $result->query);
        }

        public function testTimberAdapterPreservesArgsInWpQuery(): void
        {
            $args = ['post_type' => 'event', 'posts_per_page' => 10];
            $result = (new TimberAdapter())->postQuery($args);

            self::assertSame($args, $result->query->query_vars);
        }
    }
}
