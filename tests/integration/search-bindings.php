<?php
/**
 * Run with wp eval-file in a WordPress site using this Quartermaster checkout.
 */

use PressGang\Quartermaster\Bindings\ArrayQueryVarSource;
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Quartermaster;

$cases = [
    '' => '',
    'key=' => '',
    'key=C%2B%2B' => 'C++',
    'key=two+words' => 'two words',
    'key=%252B' => '',
    'key=%3Cb%3Eword%3C%2Fb%3E' => 'word',
    'key=caf%C3%A9' => 'café',
    'key=0' => '0',
];
$results = [];
foreach ($cases as $encoded => $expected) {
    parse_str($encoded, $values);
    $sanitizations = 0;
    $observe = static function ($value) use (&$sanitizations) {
        ++$sanitizations;
        return $value;
    };
    add_filter('sanitize_text_field', $observe);
    $args = Quartermaster::prepare()->bindQueryVars([
        'key' => Bind::relevanssi('key', allowEmpty: true, default: ''),
    ], new ArrayQueryVarSource($values))->toArgs();
    remove_filter('sanitize_text_field', $observe);
    if (($args['s'] ?? null) !== $expected || ($args['relevanssi'] ?? false) !== true || $sanitizations !== 1) {
        throw new RuntimeException('Search binding failed: ' . $encoded);
    }
    $results[$encoded] = $args;
}
parse_str('key[]=unexpected', $values);
$args = Quartermaster::prepare()->bindQueryVars([
    'key' => Bind::relevanssi('key', allowEmpty: true, default: ''),
], new ArrayQueryVarSource($values))->toArgs();
if ($args !== []) {
    throw new RuntimeException('Malformed search should leave query unchanged.');
}
echo wp_json_encode(['passed' => count($cases) + 1, 'cases' => $results], JSON_PRETTY_PRINT);
