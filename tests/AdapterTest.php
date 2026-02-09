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

    if (!class_exists(\Timber\Timber::class)) {
        class Timber
        {
            /**
             * @param array<string, mixed> $args
             * @return array<int, object>
             */
            public static function get_terms(array $args = []): array
            {
                $GLOBALS['__quartermaster_test_timber_get_terms_args'] = $args;

                return [
                    (object) ['name' => 'stub-term', 'taxonomy' => $args['taxonomy'] ?? 'category'],
                ];
            }
        }
    }
}

namespace PressGang\Quartermaster\Tests {

    use PHPUnit\Framework\TestCase;
    use PressGang\Quartermaster\Adapters\TimberAdapter;
    use PressGang\Quartermaster\Adapters\TimberTermAdapter;
    use PressGang\Quartermaster\Adapters\WpAdapter;
    use PressGang\Quartermaster\Quartermaster;

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

        public function testTimberTermAdapterReturnsIterable(): void
        {
            $result = (new TimberTermAdapter())->getTerms(['taxonomy' => 'category']);

            self::assertIsIterable($result);
        }

        public function testTimberTermAdapterPassesArgsToTimber(): void
        {
            unset($GLOBALS['__quartermaster_test_timber_get_terms_args']);

            $args = ['taxonomy' => 'post_tag', 'hide_empty' => false];
            (new TimberTermAdapter())->getTerms($args);

            self::assertSame($args, $GLOBALS['__quartermaster_test_timber_get_terms_args']);
        }

        public function testTermsBuilderTimberTerminalPassesArgsToTimber(): void
        {
            unset($GLOBALS['__quartermaster_test_timber_get_terms_args']);

            $result = Quartermaster::terms('category')->hideEmpty(false)->timber();

            self::assertIsIterable($result);
            self::assertSame(
                ['taxonomy' => 'category', 'hide_empty' => false],
                $GLOBALS['__quartermaster_test_timber_get_terms_args']
            );
        }
    }
}
