<?php

namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error
        {
        }
    }

    if (!function_exists('get_terms')) {
        /**
         * @param array<string, mixed> $args
         * @return array<int, mixed>
         */
        function get_terms(array $args = []): array
        {
            $GLOBALS['__quartermaster_test_get_terms_args'] = $args;

            return [['stub' => true, 'args' => $args]];
        }
    }
}

namespace PressGang\Quartermaster\Tests {

    use PHPUnit\Framework\TestCase;
    use PressGang\Quartermaster\Quartermaster;
    use PressGang\Quartermaster\Terms\TermsBuilder;

    final class EntrypointsTest extends TestCase
    {
        protected function setUp(): void
        {
            unset($GLOBALS['__quartermaster_test_get_terms_args']);
        }

        public function testPostsEntrypointDefaultsAreEmpty(): void
        {
            self::assertSame([], Quartermaster::posts()->toArgs());
        }

        public function testPostsEntrypointSupportsPostTypeSeed(): void
        {
            self::assertSame(['post_type' => 'event'], Quartermaster::posts('event')->toArgs());
        }

        public function testPostsEntrypointSupportsSeedArgsArray(): void
        {
            $seed = ['post_type' => 'event'];

            self::assertSame($seed, Quartermaster::posts($seed)->toArgs());
        }

        public function testTermsEntrypointDefaultsAreEmpty(): void
        {
            self::assertSame([], Quartermaster::terms()->toArgs());
        }

        public function testTermsEntrypointSupportsTaxonomySeed(): void
        {
            self::assertSame(['taxonomy' => 'category'], Quartermaster::terms('category')->toArgs());
        }

        public function testTermsEntrypointSupportsSeedArgsArray(): void
        {
            $seed = ['taxonomy' => 'category', 'hide_empty' => true];

            self::assertSame($seed, Quartermaster::terms($seed)->toArgs());
        }

        public function testTermsWhereMetaBuildsMetaQueryWithExpectedRelationHandling(): void
        {
            $args = Quartermaster::terms('category')
                ->whereMeta('featured', 1)
                ->orWhereMeta('priority', 'high')
                ->toArgs();

            self::assertSame('OR', $args['meta_query']['relation']);
            self::assertSame('featured', $args['meta_query'][0]['key']);
            self::assertSame('priority', $args['meta_query'][1]['key']);
        }

        public function testTermsGetCallsWordPressGetTermsWithCurrentArgs(): void
        {
            $result = Quartermaster::terms('category')->hideEmpty(false)->get();

            self::assertIsArray($result);
            self::assertSame(
                ['taxonomy' => 'category', 'hide_empty' => false],
                $GLOBALS['__quartermaster_test_get_terms_args'] ?? null
            );
        }

        public function testTermsBuilderIsReturnedFromTermsEntrypoint(): void
        {
            self::assertInstanceOf(TermsBuilder::class, Quartermaster::terms('category'));
        }
    }
}
