<?php

/**
 * Lightweight stubs for WP_Query and Timber\PostQuery.
 *
 * These match the Timber 2 constructor signature so the adapter
 * wiring can be tested without bootstrapping WordPress or Timber.
 */

namespace {
    if (!class_exists('WP_Term')) {
        class WP_Term
        {
            public function __construct(public int $term_id, public string $taxonomy = 'category')
            {
            }
        }
    }
    if (!class_exists('WP_Query')) {
        class WP_Query
        {
            public array $query_vars;
            public array $posts;

            public function __construct(array $args = [])
            {
                $this->query_vars = $args;
                $this->posts = [
                    (object) ['ID' => 1, 'post_type' => $args['post_type'] ?? 'post'],
                ];
            }

            public function set(string $key, mixed $value): void
            {
                $this->query_vars[$key] = $value;
            }

            public function get(string $key, mixed $default = ''): mixed
            {
                return $this->query_vars[$key] ?? $default;
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

            public function to_array(): array
            {
                return $this->query->posts;
            }
        }
    }

    if (!class_exists(\Timber\Timber::class)) {
        class Timber
        {
            public static function get_term(\WP_Term $term): object
            {
                $GLOBALS['__quartermaster_test_mapped_terms'][] = $term;
                $class = $GLOBALS['__quartermaster_test_term_class'] ?? \stdClass::class;
                $mapped = new $class();
                $mapped->term_id = $term->term_id;
                return $mapped;
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
        protected function tearDown(): void
        {
            unset(
                $GLOBALS['__quartermaster_test_term_results'],
                $GLOBALS['__quartermaster_test_mapped_terms'],
                $GLOBALS['__quartermaster_test_term_class']
            );
        }

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

        public function testTimberTermAdapterPassesArgsToWordPress(): void
        {
            unset($GLOBALS['__quartermaster_test_get_terms_args']);

            $args = ['taxonomy' => 'post_tag', 'hide_empty' => false];
            (new TimberTermAdapter())->getTerms($args);

            self::assertSame($args, $GLOBALS['__quartermaster_test_get_terms_args']);
        }

        public function testPostsGetReturnsPostsArray(): void
        {
            $result = Quartermaster::posts('event')->get();

            self::assertIsArray($result);
            self::assertNotEmpty($result);
            self::assertSame('event', $result[0]->post_type);
        }

        public function testPostsGetAndWpQueryReturnSamePosts(): void
        {
            $builder = Quartermaster::posts('event');

            self::assertEquals($builder->wpQuery()->posts, $builder->get());
        }

        public function testToArrayReturnsArray(): void
        {
            $result = Quartermaster::posts('event')->toArray();

            self::assertIsArray($result);
            self::assertNotEmpty($result);
            self::assertSame('event', $result[0]->post_type);
        }

        public function testToArrayRecordsTimberEngineInExplain(): void
        {
            $builder = Quartermaster::posts('event');
            $builder->toArray();
            $explain = $builder->explain();

            $terminal = end($explain['applied']);
            self::assertSame('toArray', $terminal['name']);
            self::assertSame(['timber'], $terminal['params']);
        }

        public function testTermsBuilderTimberTerminalPassesArgsToWordPress(): void
        {
            unset($GLOBALS['__quartermaster_test_get_terms_args']);

            $result = Quartermaster::terms('category')->hideEmpty(false)->timber();

            self::assertIsIterable($result);
            self::assertSame(
                ['taxonomy' => 'category', 'hide_empty' => false],
                $GLOBALS['__quartermaster_test_get_terms_args']
            );
        }
        public function testTermResultsKeepTheirFilteredOrderAndKeys(): void
        {
            $terms = [8 => new \WP_Term(30), 2 => new \WP_Term(10)];
            $GLOBALS['__quartermaster_test_term_results'] = $terms;

            $result = Quartermaster::terms('category')->timber();

            self::assertSame([8, 2], array_keys($result));
            self::assertSame([30, 10], array_column($result, 'term_id'));
            self::assertSame(array_values($terms), $GLOBALS['__quartermaster_test_mapped_terms']);
        }

        public function testEmptyTermsDoNotInvokeTimber(): void
        {
            $GLOBALS['__quartermaster_test_term_results'] = [];

            self::assertSame([], Quartermaster::terms('category')->timber());
            self::assertArrayNotHasKey('__quartermaster_test_mapped_terms', $GLOBALS);
        }

        public function testTermConversionUsesTimberClassMapping(): void
        {
            $GLOBALS['__quartermaster_test_term_results'] = [new \WP_Term(10)];
            $GLOBALS['__quartermaster_test_term_class'] = MappedTerm::class;

            $result = Quartermaster::terms('category')->timber();

            self::assertInstanceOf(MappedTerm::class, $result[0]);
        }

        public function testScalarProjectionsAreNotConvertedOrReindexed(): void
        {
            foreach (['ids' => [30, 10], 'id=>name' => [30 => 'First', 10 => 'Second']] as $fields => $terms) {
                $GLOBALS['__quartermaster_test_term_results'] = $terms;

                self::assertSame($terms, Quartermaster::terms('category')->fields($fields)->timber());
                self::assertArrayNotHasKey('__quartermaster_test_mapped_terms', $GLOBALS);
            }
        }

        public function testWordPressErrorsAreReported(): void
        {
            $GLOBALS['__quartermaster_test_term_results'] = new \WP_Error();
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Invalid taxonomy.');

            Quartermaster::terms('missing')->timber();
        }

        public function testCountResultsAreRejectedExplicitly(): void
        {
            $GLOBALS['__quartermaster_test_term_results'] = '3';
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('count queries are not supported');

            Quartermaster::terms('category')->fields('count')->timber();
        }

    }
    class MappedTerm
    {
        public int $term_id;
    }

}
