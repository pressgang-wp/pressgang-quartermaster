<?php


namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Bindings\ArrayQueryVarSource;
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Bindings\Binder;
use PressGang\Quartermaster\Quartermaster;

final class QuartermasterSmokeTest extends TestCase
{
    public function testPrepareDefaultsAreEmpty(): void
    {
        self::assertSame([], Quartermaster::prepare()->toArgs());
    }

    public function testPrepareHasNoImplicitPagination(): void
    {
        $args = Quartermaster::prepare()->toArgs();

        self::assertArrayNotHasKey('posts_per_page', $args);
        self::assertArrayNotHasKey('paged', $args);
    }

    public function testPrepareHasNoImplicitMetaOrTaxQuery(): void
    {
        $args = Quartermaster::prepare()->toArgs();

        self::assertArrayNotHasKey('meta_query', $args);
        self::assertArrayNotHasKey('tax_query', $args);
    }

    public function testBindQueryVarsMapModeAppliesExpectedClauses(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => ['loop'],
            'min_distance' => '10',
            'search' => 'abc',
        ]);

        $args = Quartermaster::prepare('route')->bindQueryVars([
            'shape' => Bind::tax('route_shape'),
            'min_distance' => Bind::metaNum('distance_miles', '>='),
            'search' => Bind::search(),
        ], $source)->toArgs();

        self::assertSame('route_shape', $args['tax_query'][0]['taxonomy']);
        self::assertSame(['loop'], $args['tax_query'][0]['terms']);
        self::assertSame('distance_miles', $args['meta_query'][0]['key']);
        self::assertSame(10.0, $args['meta_query'][0]['value']);
        self::assertSame('NUMERIC', $args['meta_query'][0]['type']);
        self::assertSame('abc', $args['s']);
    }

    public function testBindQueryVarsBinderModeMatchesMapModeExactly(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => ['loop'],
            'min_distance' => '10',
            'search' => 'abc',
        ]);

        $mapArgs = Quartermaster::prepare('route')->bindQueryVars([
            'shape' => Bind::tax('route_shape'),
            'min_distance' => Bind::metaNum('distance_miles', '>='),
            'search' => Bind::search(),
        ], $source)->toArgs();

        $binderArgs = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('shape', 'route_shape');
            $b->metaNum('min_distance')->to('distance_miles', '>=');
            $b->search('search');
        }, $source)->toArgs();

        self::assertSame($mapArgs, $binderArgs);
    }

    public function testBindQueryVarsBinderModeMatchesMapModeExplainOutput(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => ['loop'],
            'min_distance' => '10',
            'search' => 'abc',
        ]);

        $mapExplain = Quartermaster::prepare('route')->bindQueryVars([
            'shape' => Bind::tax('route_shape'),
            'min_distance' => Bind::metaNum('distance_miles', '>='),
            'search' => Bind::search(),
        ], $source)->explain();

        $binderExplain = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('shape', 'route_shape');
            $b->metaNum('min_distance')->to('distance_miles', '>=');
            $b->search('search');
        }, $source)->explain();

        self::assertSame($mapExplain, $binderExplain);
    }

    public function testBindQueryVarsBinderTaxDefaultsToQueryVarTaxonomy(): void
    {
        $source = new ArrayQueryVarSource([
            'district' => ['north'],
        ]);

        $args = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('district');
        }, $source)->toArgs();

        self::assertSame('district', $args['tax_query'][0]['taxonomy']);
        self::assertSame('slug', $args['tax_query'][0]['field']);
        self::assertSame(['north'], $args['tax_query'][0]['terms']);
        self::assertSame('IN', $args['tax_query'][0]['operator']);
    }

    public function testBindQueryVarsBinderTaxExplicitTaxonomyMappingWorks(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => ['loop'],
        ]);

        $args = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('shape', 'route_shape');
        }, $source)->toArgs();

        self::assertSame('route_shape', $args['tax_query'][0]['taxonomy']);
    }

    public function testBindQueryVarsBinderDefaultTaxMatchesMapTaxBinding(): void
    {
        $source = new ArrayQueryVarSource([
            'district' => ['north'],
        ]);

        $binderArgs = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('district');
        }, $source)->toArgs();

        $mapArgs = Quartermaster::prepare('route')->bindQueryVars([
            'district' => Bind::tax('district'),
        ], $source)->toArgs();

        self::assertSame($mapArgs, $binderArgs);
    }

    public function testPrepareSeedsPostTypeWhenProvided(): void
    {
        self::assertSame(['post_type' => 'event'], Quartermaster::prepare('event')->toArgs());
    }

    public function testPrepareSupportsArrayPostTypes(): void
    {
        self::assertSame(['post_type' => ['post', 'page']], Quartermaster::prepare(['post', 'page'])->toArgs());
    }

    public function testPreparePostTypeSeedMatchesExplicitPostTypeCall(): void
    {
        self::assertSame(
            Quartermaster::prepare('event')->toArgs(),
            Quartermaster::prepare()->postType('event')->toArgs(),
        );
    }

    public function testPreparePostTypeSeedCanBeOverriddenExplicitly(): void
    {
        self::assertSame(
            ['post_type' => 'post'],
            Quartermaster::prepare('event')->postType('post')->toArgs(),
        );
    }

    public function testBindQueryVarsSkipLogicDoesNotAddClausesForEmptyValues(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => [],
            'min_distance' => '',
            'search' => null,
        ]);

        $args = Quartermaster::prepare('route')->bindQueryVars([
            'shape' => Bind::tax('route_shape'),
            'min_distance' => Bind::metaNum('distance_miles', '>='),
            'search' => Bind::search(),
        ], $source)->toArgs();

        self::assertSame(['post_type' => 'route'], $args);
    }

    public function testBindQueryVarsBinderTaxSkipsForEmptyTerms(): void
    {
        $source = new ArrayQueryVarSource([
            'district' => [''],
        ]);

        $args = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
            $b->tax('district');
        }, $source)->toArgs();

        self::assertSame(['post_type' => 'route'], $args);
    }

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

    public function testSearchSetsSearchArgWhenProvided(): void
    {
        $args = Quartermaster::prepare()->search('abc')->toArgs();

        self::assertArrayHasKey('s', $args);
        self::assertSame('abc', $args['s']);
    }

    public function testWhereMetaCreatesMetaQueryArray(): void
    {
        $args = Quartermaster::prepare()->whereMeta('start', '2026-01-01', '>=', 'DATE')->toArgs();

        self::assertArrayHasKey('meta_query', $args);
        self::assertIsArray($args['meta_query']);
        self::assertArrayHasKey(0, $args['meta_query']);
        self::assertSame('start', $args['meta_query'][0]['key']);
    }

    public function testWhereMetaDateIsFluent(): void
    {
        $builder = Quartermaster::prepare()->whereMetaDate('start', '>=');

        self::assertInstanceOf(Quartermaster::class, $builder);
    }

    public function testWhereMetaDateBuildsDateClauseWithExplicitValue(): void
    {
        $args = Quartermaster::prepare()->whereMetaDate('start', '>=', '20260208')->toArgs();

        self::assertArrayHasKey('meta_query', $args);
        self::assertSame('start', $args['meta_query'][0]['key']);
        self::assertSame('20260208', $args['meta_query'][0]['value']);
        self::assertSame('>=', $args['meta_query'][0]['compare']);
        self::assertSame('DATE', $args['meta_query'][0]['type']);
    }

    public function testWhereMetaDateDoesNotSetOrderingArgsByItself(): void
    {
        $args = Quartermaster::prepare()->whereMetaDate('start', '>=', '20260208')->toArgs();

        self::assertArrayNotHasKey('meta_key', $args);
        self::assertArrayNotHasKey('orderby', $args);
        self::assertArrayNotHasKey('order', $args);
    }

    public function testWhereMetaDateThenOrderByMetaSetsExplicitOrdering(): void
    {
        $args = Quartermaster::prepare()
            ->whereMetaDate('start', '>=', '20260208')
            ->orderByMeta('start', 'ASC')
            ->toArgs();

        self::assertSame('start', $args['meta_key']);
        self::assertSame('meta_value', $args['orderby']);
        self::assertSame('ASC', $args['order']);
    }

    public function testWhereDateCreatesDateQueryArray(): void
    {
        $args = Quartermaster::prepare()->whereDate(['year' => 2026])->toArgs();

        self::assertArrayHasKey('date_query', $args);
        self::assertIsArray($args['date_query']);
        self::assertArrayHasKey(0, $args['date_query']);
        self::assertSame(2026, $args['date_query'][0]['year']);
    }

    public function testWhereDateAfterCreatesDateQueryClause(): void
    {
        $args = Quartermaster::prepare()->whereDateAfter('2026-01-01')->toArgs();

        self::assertSame('2026-01-01', $args['date_query'][0]['after']);
        self::assertTrue($args['date_query'][0]['inclusive']);
    }

    public function testWhereDateBeforeCreatesDateQueryClause(): void
    {
        $args = Quartermaster::prepare()->whereDateBefore('2026-12-31', false)->toArgs();

        self::assertSame('2026-12-31', $args['date_query'][0]['before']);
        self::assertFalse($args['date_query'][0]['inclusive']);
    }

    public function testWhereDateNormalizesRelationForMultipleClauses(): void
    {
        $args = Quartermaster::prepare()
            ->whereDate(['year' => 2026])
            ->whereDate(['month' => 2])
            ->toArgs();

        self::assertSame('AND', $args['date_query']['relation']);
    }

    public function testWhereMetaPreservesSeededNamedClauseKeys(): void
    {
        $seed = [
            'meta_query' => [
                'price_clause' => [
                    'key' => 'price',
                    'value' => 100,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ],
            ],
        ];

        $args = (new Quartermaster($seed))->whereMeta('start', '2026-01-01')->toArgs();

        self::assertArrayHasKey('price_clause', $args['meta_query']);
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

    public function testWhereIdSetsPostIdArg(): void
    {
        $args = Quartermaster::prepare()->whereId(42)->toArgs();

        self::assertSame(42, $args['p']);
    }

    public function testWhereInIdsSetsPostInArg(): void
    {
        $args = Quartermaster::prepare()->whereInIds([1, 2])->toArgs();

        self::assertSame([1, 2], $args['post__in']);
    }

    public function testWhereInIdsIgnoresInvalidValues(): void
    {
        $args = Quartermaster::prepare()->whereInIds([1, 'a', null, 2])->toArgs();

        self::assertSame([1, 2], $args['post__in']);
    }

    public function testWhereInIdsDoesNotMutateForEmptyInput(): void
    {
        $args = Quartermaster::prepare()->whereInIds([])->toArgs();

        self::assertArrayNotHasKey('post__in', $args);
    }

    public function testExcludeIdsSetsPostNotInArg(): void
    {
        $args = Quartermaster::prepare()->excludeIds([3, 4])->toArgs();

        self::assertSame([3, 4], $args['post__not_in']);
    }

    public function testExcludeIdsDoesNotMutateForInvalidInput(): void
    {
        $args = Quartermaster::prepare()->excludeIds(['x', null])->toArgs();

        self::assertArrayNotHasKey('post__not_in', $args);
    }

    public function testWhereParentSetsPostParentArg(): void
    {
        $args = Quartermaster::prepare()->whereParent(9)->toArgs();

        self::assertSame(9, $args['post_parent']);
    }

    public function testWhereParentInSetsPostParentInArg(): void
    {
        $args = Quartermaster::prepare()->whereParentIn([5, 6])->toArgs();

        self::assertSame([5, 6], $args['post_parent__in']);
    }

    public function testWhereParentInDoesNotMutateForEmptyInput(): void
    {
        $args = Quartermaster::prepare()->whereParentIn([])->toArgs();

        self::assertArrayNotHasKey('post_parent__in', $args);
    }

    public function testWhereAuthorSetsAuthorArg(): void
    {
        $args = Quartermaster::prepare()->whereAuthor(11)->toArgs();

        self::assertSame(11, $args['author']);
    }

    public function testWhereAuthorInSetsAuthorInArg(): void
    {
        $args = Quartermaster::prepare()->whereAuthorIn([7, 8])->toArgs();

        self::assertSame([7, 8], $args['author__in']);
    }

    public function testWhereAuthorNotInSetsAuthorNotInArg(): void
    {
        $args = Quartermaster::prepare()->whereAuthorNotIn([3, 5])->toArgs();

        self::assertSame([3, 5], $args['author__not_in']);
    }

    public function testWhereAuthorInDoesNotMutateForEmptyInput(): void
    {
        $args = Quartermaster::prepare()->whereAuthorIn([])->toArgs();

        self::assertArrayNotHasKey('author__in', $args);
    }

    public function testWhereAuthorNotInDoesNotMutateForInvalidInput(): void
    {
        $args = Quartermaster::prepare()->whereAuthorNotIn(['x', null])->toArgs();

        self::assertArrayNotHasKey('author__not_in', $args);
    }

    public function testIdsOnlySetsFieldsIds(): void
    {
        $args = Quartermaster::prepare()->idsOnly()->toArgs();

        self::assertSame('ids', $args['fields']);
    }

    public function testNoFoundRowsSetsFlag(): void
    {
        $args = Quartermaster::prepare()->noFoundRows()->toArgs();

        self::assertTrue($args['no_found_rows']);
    }

    public function testWithMetaCacheSetsFlag(): void
    {
        $args = Quartermaster::prepare()->withMetaCache(false)->toArgs();

        self::assertFalse($args['update_post_meta_cache']);
    }

    public function testWithTermCacheSetsFlag(): void
    {
        $args = Quartermaster::prepare()->withTermCache(false)->toArgs();

        self::assertFalse($args['update_post_term_cache']);
    }

    public function testOrderByMetaNumericSetsMetaValueNumOrderby(): void
    {
        $args = Quartermaster::prepare()->orderByMetaNumeric('price')->toArgs();

        self::assertSame('price', $args['meta_key']);
        self::assertSame('meta_value_num', $args['orderby']);
        self::assertSame('ASC', $args['order']);
    }

    public function testOrderByDescHelperSetsDescendingOrder(): void
    {
        $args = Quartermaster::prepare('post')->orderByDesc('date')->toArgs();

        self::assertSame('date', $args['orderby']);
        self::assertSame('DESC', $args['order']);
    }

    public function testOrderByAscHelperSetsAscendingOrder(): void
    {
        $args = Quartermaster::prepare('post')->orderByAsc('title')->toArgs();

        self::assertSame('title', $args['orderby']);
        self::assertSame('ASC', $args['order']);
    }

    public function testOrderByMetaDescHelperSetsExpectedMetaArgs(): void
    {
        $args = Quartermaster::prepare('event')->orderByMetaDesc('start', 'DATE')->toArgs();

        self::assertSame('start', $args['meta_key']);
        self::assertSame('meta_value', $args['orderby']);
        self::assertSame('DESC', $args['order']);
        self::assertSame('DATE', $args['meta_type']);
    }

    public function testOrderByMetaNumericDescHelperSetsExpectedNumericMetaArgs(): void
    {
        $args = Quartermaster::prepare('product')->orderByMetaNumericDesc('price')->toArgs();

        self::assertSame('price', $args['meta_key']);
        self::assertSame('meta_value_num', $args['orderby']);
        self::assertSame('DESC', $args['order']);
    }

    public function testOrderByInvalidDirectionFallsBackAndWarns(): void
    {
        $q = Quartermaster::prepare('post')->orderBy('date', 'banana');
        $args = $q->toArgs();
        $explain = $q->explain();

        self::assertSame('DESC', $args['order']);
        self::assertContains("Invalid order direction 'banana' in orderBy(); defaulted to 'DESC'.", $explain['warnings']);
    }

    public function testOrderByMetaInvalidDirectionFallsBackAndWarns(): void
    {
        $q = Quartermaster::prepare('event')->orderByMeta('start', 'sideways', 'DATE');
        $args = $q->toArgs();
        $explain = $q->explain();

        self::assertSame('ASC', $args['order']);
        self::assertContains("Invalid order direction 'sideways' in orderByMeta(); defaulted to 'ASC'.", $explain['warnings']);
    }

    public function testWhereTaxPreservesSeededNamedClauseKeys(): void
    {
        $seed = [
            'tax_query' => [
                'topic_clause' => [
                    'taxonomy' => 'topic',
                    'field' => 'slug',
                    'terms' => ['news'],
                    'operator' => 'IN',
                ],
            ],
        ];

        $args = (new Quartermaster($seed))->whereTax('region', ['us'])->toArgs();

        self::assertArrayHasKey('topic_clause', $args['tax_query']);
    }

    public function testPagedCanBeProvidedWithoutWordPressRuntime(): void
    {
        $args = Quartermaster::prepare()->paged(12, 0)->toArgs();

        self::assertSame(12, $args['posts_per_page']);
        self::assertSame(1, $args['paged']);
    }

    public function testAllSetsFetchAllPaginationArgs(): void
    {
        $args = Quartermaster::prepare('post')->all()->toArgs();

        self::assertSame('post', $args['post_type']);
        self::assertSame(-1, $args['posts_per_page']);
        self::assertTrue($args['nopaging']);
    }

    public function testAllUnsetsPagedWhenPagedWasPreviouslyConfigured(): void
    {
        $args = Quartermaster::prepare('post')->paged(10, 2)->all()->toArgs();

        self::assertSame(-1, $args['posts_per_page']);
        self::assertTrue($args['nopaging']);
        self::assertArrayNotHasKey('paged', $args);
    }

    public function testAllIsRecordedInExplainAppliedCalls(): void
    {
        $explain = Quartermaster::prepare('post')->all()->explain();

        self::assertContains('all', array_column($explain['applied'], 'name'));
    }

    public function testExplainIncludesWarnings(): void
    {
        $explain = Quartermaster::prepare()->orderBy('meta_value')->explain();

        self::assertArrayHasKey('warnings', $explain);
        self::assertNotEmpty($explain['warnings']);
    }

    public function testExplainIncludesBindingSummariesWhenBindingsRun(): void
    {
        $source = new ArrayQueryVarSource([
            'shape' => ['loop'],
            'search' => '',
        ]);

        $explain = Quartermaster::prepare('route')->bindQueryVars([
            'shape' => Bind::tax('route_shape'),
            'search' => Bind::search(),
        ], $source)->explain();

        self::assertArrayHasKey('bindings', $explain);
        self::assertSame('shape', $explain['bindings'][0]['key']);
        self::assertTrue($explain['bindings'][0]['applied']);
        self::assertSame('array(len=1)', $explain['bindings'][0]['value']);
        self::assertSame('search', $explain['bindings'][1]['key']);
        self::assertFalse($explain['bindings'][1]['applied']);
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
