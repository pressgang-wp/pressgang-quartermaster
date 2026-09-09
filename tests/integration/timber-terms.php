<?php

/**
 * Run with WordPress and Timber loaded, using this checkout's adapter:
 * wp --require=src/Adapters/TimberTermAdapter.php eval-file tests/integration/timber-terms.php
 */

use PressGang\Quartermaster\Adapters\TimberTermAdapter;
use Timber\Term;

class QuartermasterIntegrationTerm extends Term
{
}

$adapter = new TimberTermAdapter();
$terms = array_map(
    static fn (int $id): WP_Term => new WP_Term((object) [
        'term_id' => $id,
        'term_taxonomy_id' => $id,
        'name' => 'Term ' . $id,
        'slug' => 'term-' . $id,
        'taxonomy' => 'category',
        'parent' => 0,
        'count' => 1,
        'description' => '',
        'term_group' => 0,
    ]),
    [30, 10]
);
$filteredResults = $terms;
$filterCalls = 0;
$resultFilter = static function ($results, $taxonomies, $args) use (&$filteredResults, &$filterCalls) {
    if (!empty($args['quartermaster_integration'])) {
        ++$filterCalls;
        return $filteredResults;
    }
    return $results;
};
$classMap = static function (array $map): array {
    $map['category'] = QuartermasterIntegrationTerm::class;
    return $map;
};
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

add_filter('get_terms', $resultFilter, PHP_INT_MAX, 3);
add_filter('timber/term/classmap', $classMap);

try {
    $args = ['taxonomy' => 'category', 'hide_empty' => false, 'quartermaster_integration' => true];
    $result = $adapter->getTerms($args);
    $assert($filterCalls === 1, 'The WordPress get_terms result filter must run once.');
    $assert(wp_list_pluck($result, 'term_id') === [30, 10], 'Filtered term order changed.');
    $assert($result[0] instanceof QuartermasterIntegrationTerm, 'Timber term class mapping was bypassed.');

    $filteredResults = [];
    $assert($adapter->getTerms($args) === [], 'An empty result must stay empty.');

    $filteredResults = [30 => 'First', 10 => 'Second'];
    $assert($adapter->getTerms($args + ['fields' => 'id=>name']) === $filteredResults, 'Scalar projection keys or values changed.');

    echo "WordPress filters, ordering, Timber class mapping, empty results and scalar projections pass.\n";
} finally {
    remove_filter('get_terms', $resultFilter, PHP_INT_MAX);
    remove_filter('timber/term/classmap', $classMap);
}
