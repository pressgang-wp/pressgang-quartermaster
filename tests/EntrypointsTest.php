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

        public function testTermsObjectIdsSetsObjectIds(): void
        {
            $args = Quartermaster::terms('category')->objectIds([1, 2, 3])->toArgs();

            self::assertSame([1, 2, 3], $args['object_ids']);
        }

        public function testTermsObjectIdsSupportsSingleInt(): void
        {
            $args = Quartermaster::terms('category')->objectIds(42)->toArgs();

            self::assertSame(42, $args['object_ids']);
        }

        public function testTermsSlugSetsSlug(): void
        {
            $args = Quartermaster::terms('category')->slug('featured')->toArgs();

            self::assertSame('featured', $args['slug']);
        }

        public function testTermsSlugSupportsArray(): void
        {
            $args = Quartermaster::terms('category')->slug(['featured', 'popular'])->toArgs();

            self::assertSame(['featured', 'popular'], $args['slug']);
        }

        public function testTermsNameSetsName(): void
        {
            $args = Quartermaster::terms('category')->name('Uncategorized')->toArgs();

            self::assertSame('Uncategorized', $args['name']);
        }

        public function testTermsFieldsSetsFields(): void
        {
            $args = Quartermaster::terms('category')->fields('ids')->toArgs();

            self::assertSame('ids', $args['fields']);
        }

        public function testTermsExcludeTreeSetsExcludeTree(): void
        {
            $args = Quartermaster::terms('category')->excludeTree([5, 10])->toArgs();

            self::assertSame([5, 10], $args['exclude_tree']);
        }

        public function testTermsChildOfSetsChildOf(): void
        {
            $args = Quartermaster::terms('category')->childOf(7)->toArgs();

            self::assertSame(7, $args['child_of']);
        }

        public function testTermsChildlessSetsChildless(): void
        {
            $args = Quartermaster::terms('category')->childless()->toArgs();

            self::assertTrue($args['childless']);
        }

        public function testTermsWhenTrueAppliesClosure(): void
        {
            $args = Quartermaster::terms('category')
                ->when(true, fn (TermsBuilder $q) => $q->hideEmpty(false))
                ->toArgs();

            self::assertFalse($args['hide_empty']);
        }

        public function testTermsWhenFalseSkipsClosure(): void
        {
            $args = Quartermaster::terms('category')
                ->when(false, fn (TermsBuilder $q) => $q->hideEmpty(false))
                ->toArgs();

            self::assertArrayNotHasKey('hide_empty', $args);
        }

        public function testTermsExplainWarnsWhenHideEmptyNotSet(): void
        {
            $explain = Quartermaster::terms('category')->explain();

            self::assertContains(
                'hide_empty was not explicitly set; WordPress defaults to true, which excludes terms with no posts.',
                $explain['warnings']
            );
        }

        public function testTermsExplainDoesNotWarnWhenHideEmptyIsSet(): void
        {
            $explain = Quartermaster::terms('category')->hideEmpty(false)->explain();

            self::assertNotContains(
                'hide_empty was not explicitly set; WordPress defaults to true, which excludes terms with no posts.',
                $explain['warnings']
            );
        }

        public function testTermsFluentChainCombinesArgs(): void
        {
            $args = Quartermaster::terms('category')
                ->objectIds(42)
                ->hideEmpty(false)
                ->fields('ids')
                ->childOf(3)
                ->limit(10)
                ->orderBy('count', 'DESC')
                ->toArgs();

            self::assertSame('category', $args['taxonomy']);
            self::assertSame(42, $args['object_ids']);
            self::assertFalse($args['hide_empty']);
            self::assertSame('ids', $args['fields']);
            self::assertSame(3, $args['child_of']);
            self::assertSame(10, $args['number']);
            self::assertSame('count', $args['orderby']);
            self::assertSame('DESC', $args['order']);
        }
    }
}
