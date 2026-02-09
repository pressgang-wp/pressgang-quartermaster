<?php

use PressGang\Quartermaster\Quartermaster;
use PressGang\Quartermaster\Terms\TermsBuilder;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @param ReflectionType|null $type
 * @return string
 */
function render_type(?ReflectionType $type): string
{
    if ($type === null) {
        return 'mixed';
    }

    if ($type instanceof ReflectionNamedType) {
        $name = $type->getName();

        if ($name === 'null') {
            return 'null';
        }

        return $type->allowsNull() && $name !== 'mixed' ? $name . '|null' : $name;
    }

    if ($type instanceof ReflectionUnionType) {
        $parts = array_map(
            static fn (ReflectionType $part): string => render_type($part),
            $type->getTypes()
        );

        return implode('|', $parts);
    }

    if ($type instanceof ReflectionIntersectionType) {
        $parts = array_map(
            static fn (ReflectionType $part): string => render_type($part),
            $type->getTypes()
        );

        return implode('&', $parts);
    }

    return 'mixed';
}

/**
 * @param ReflectionParameter $parameter
 * @return string
 */
function render_parameter(ReflectionParameter $parameter): string
{
    $type = render_type($parameter->getType());
    $prefix = $parameter->isVariadic() ? '...' : '';
    $byRef = $parameter->isPassedByReference() ? '&' : '';
    $rendered = $type . ' ' . $prefix . $byRef . '$' . $parameter->getName();

    if ($parameter->isDefaultValueAvailable() && !$parameter->isVariadic()) {
        if ($parameter->isDefaultValueConstant()) {
            $default = (string) $parameter->getDefaultValueConstantName();
        } else {
            $default = var_export($parameter->getDefaultValue(), true);
            $default = str_replace("\n", '', $default);
        }

        $rendered .= ' = ' . $default;
    }

    return $rendered;
}

/**
 * @param ReflectionMethod $method
 * @return array{sets_args: array<int, string>, notes: string, wp_docs: array<int, string>}
 */
function parse_docblock_metadata(ReflectionMethod $method): array
{
    $doc = (string) $method->getDocComment();

    $setsArgs = [];
    $hasSetsLine = false;
    if (preg_match('/Sets:\s*(.+)/i', $doc, $match) === 1) {
        $hasSetsLine = true;
        $raw = trim($match[1]);

        if ($raw !== '' && stripos($raw, '(none)') === false && stripos($raw, '(dynamic)') === false) {
            $setsArgs = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }
    }

    preg_match_all('/https:\/\/developer\.wordpress\.org\/[^\s*]+|https:\/\/timber\.github\.io\/[^\s*]+/i', $doc, $urlMatches);
    $wpDocs = array_values(array_unique($urlMatches[0] ?? []));

    $notes = '(args mapping not annotated yet)';
    foreach (preg_split('/\R/', $doc) as $line) {
        $line = trim($line);
        $line = ltrim($line, "/* \t");

        if ($line === '' || str_starts_with($line, '@') || str_starts_with($line, 'Sets:') || str_starts_with($line, 'See:')) {
            continue;
        }

        $notes = $line;
        break;
    }

    if ($setsArgs === [] && !$hasSetsLine && $notes !== '(args mapping not annotated yet)') {
        $notes .= ' (args mapping not annotated yet)';
    }

    return [
        'sets_args' => $setsArgs,
        'notes' => $notes,
        'wp_docs' => $wpDocs,
    ];
}

$groupedMethods = [
    'Bootstrap' => ['posts', 'terms', 'prepare'],
    'Core post constraints' => ['postType', 'status', 'whereId', 'whereInIds', 'excludeIds', 'whereParent', 'whereParentIn'],
    'Author constraints' => ['whereAuthor', 'whereAuthorIn', 'whereAuthorNotIn'],
    'Pagination / search' => ['paged', 'all', 'search'],
    'Query-var binding' => ['bindQueryVars'],
    'Ordering' => [
        'orderBy',
        'orderByAsc',
        'orderByDesc',
        'orderByMeta',
        'orderByMetaAsc',
        'orderByMetaDesc',
        'orderByMetaNumeric',
        'orderByMetaNumericAsc',
        'orderByMetaNumericDesc',
    ],
    'Meta query' => ['whereMeta', 'orWhereMeta', 'whereMetaDate'],
    'Tax query' => ['whereTax'],
    'Date query' => ['whereDate', 'whereDateAfter', 'whereDateBefore'],
    'Query-shaping flags' => ['idsOnly', 'noFoundRows', 'withMetaCache', 'withTermCache'],
    'Escape hatch' => ['tapArgs'],
    'Introspection' => ['toArgs', 'explain'],
    'Terminals' => ['wpQuery', 'timber'],
];

$readsGlobals = [
    'paged' => true,
    'bindQueryVars' => true,
];

$termsGroupedMethods = [
    'Terms core' => ['taxonomy', 'objectIds', 'hideEmpty', 'slug', 'name', 'fields', 'include', 'exclude', 'excludeTree', 'parent', 'childOf', 'childless', 'search'],
    'Terms pagination / ordering' => ['limit', 'offset', 'page', 'orderBy'],
    'Terms meta query' => ['whereMeta', 'orWhereMeta'],
    'Terms escape hatch' => ['tapArgs'],
    'Terms introspection' => ['toArgs', 'explain'],
    'Terms terminal' => ['get'],
];

$ref = new ReflectionClass(Quartermaster::class);
$termsRef = new ReflectionClass(TermsBuilder::class);
$methods = [];

foreach ($groupedMethods as $group => $methodNames) {
    foreach ($methodNames as $methodName) {
        if (!$ref->hasMethod($methodName)) {
            continue;
        }

        $method = $ref->getMethod($methodName);
        $signature = $method->getName() . '(' . implode(', ', array_map('render_parameter', $method->getParameters())) . '): ' . render_type($method->getReturnType());
        $meta = parse_docblock_metadata($method);

        $methods[] = [
            'name' => $method->getName(),
            'signature' => $signature,
            'group' => $group,
            'sets_args' => $meta['sets_args'],
            'reads_globals' => $readsGlobals[$method->getName()] ?? false,
            'wp_docs' => $meta['wp_docs'],
            'notes' => $meta['notes'],
        ];
    }
}

foreach ($termsGroupedMethods as $group => $methodNames) {
    foreach ($methodNames as $methodName) {
        if (!$termsRef->hasMethod($methodName)) {
            continue;
        }

        $method = $termsRef->getMethod($methodName);

        if (!$method->isPublic()) {
            continue;
        }

        $signature = $method->getName() . '(' . implode(', ', array_map('render_parameter', $method->getParameters())) . '): ' . render_type($method->getReturnType());
        $meta = parse_docblock_metadata($method);

        $methods[] = [
            'name' => $method->getName(),
            'signature' => $signature,
            'group' => $group,
            'sets_args' => $meta['sets_args'],
            'reads_globals' => false,
            'wp_docs' => $meta['wp_docs'],
            'notes' => $meta['notes'],
        ];
    }
}

$payload = [
    'package' => 'pressgang/quartermaster',
    'version' => '0.1.0',
    'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
    'entrypoint' => 'PressGang\\Quartermaster\\Quartermaster',
    'principles' => [
        'Args-first; outputs plain WP_Query arrays',
        'No defaults unless explicitly requested',
        'Opt-in query-var binding only',
        'Explicit over magic',
    ],
    'methods' => $methods,
];

$docsDir = __DIR__ . '/../docs';
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0777, true);
}

$target = $docsDir . '/api-index.json';
file_put_contents($target, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

fwrite(STDOUT, "Generated {$target}\n");
