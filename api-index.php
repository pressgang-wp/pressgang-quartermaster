<?php

/**
 * API index manifest for pressgang/quartermaster.
 *
 * Consumed by `wp capstan make api-index`, which reflects the classes and
 * methods named here into the standard docs/api-index.json schema. Keep the
 * groups and method lists in sync as the public API changes.
 */

use PressGang\Quartermaster\Quartermaster;
use PressGang\Quartermaster\Terms\TermsBuilder;

return [
    'package' => 'pressgang/quartermaster',
    'version' => '0.1.0',
    'entrypoint' => Quartermaster::class,
    'principles' => [
        'Args-first; outputs plain WP_Query arrays',
        'No defaults unless explicitly requested',
        'Opt-in query-var binding only',
        'Explicit over magic',
    ],
    'annotate_args' => true,
    'reads_globals' => [
        'paged' => true,
        'bindQueryVars' => true,
    ],
    'groups' => [
        'Bootstrap' => [Quartermaster::class, ['posts', 'terms', 'prepare']],
        'Core post constraints' => [Quartermaster::class, ['postType', 'status', 'whereId', 'whereInIds', 'excludeIds', 'whereParent', 'whereParentIn']],
        'Author constraints' => [Quartermaster::class, ['whereAuthor', 'whereAuthorIn', 'whereAuthorNotIn']],
        'Pagination / search' => [Quartermaster::class, ['paged', 'all', 'search']],
        'Query-var binding' => [Quartermaster::class, ['bindQueryVars']],
        'Ordering' => [Quartermaster::class, [
            'orderBy', 'orderByAsc', 'orderByDesc',
            'orderByMeta', 'orderByMetaAsc', 'orderByMetaDesc',
            'orderByMetaNumeric', 'orderByMetaNumericAsc', 'orderByMetaNumericDesc',
        ]],
        'Meta query' => [Quartermaster::class, ['whereMeta', 'orWhereMeta', 'whereMetaDate']],
        'Tax query' => [Quartermaster::class, ['whereTax', 'orWhereTax']],
        'Date query' => [Quartermaster::class, ['whereDate', 'whereDateAfter', 'whereDateBefore']],
        'Query-shaping flags' => [Quartermaster::class, ['idsOnly', 'noFoundRows', 'withMetaCache', 'withTermCache']],
        'Conditional & hooks' => [Quartermaster::class, ['when', 'unless', 'tap']],
        'Macros' => [Quartermaster::class, ['macro', 'hasMacro', 'flushMacros']],
        'Escape hatch' => [Quartermaster::class, ['tapArgs']],
        'Introspection' => [Quartermaster::class, ['toArgs', 'explain']],
        'Terminals' => [Quartermaster::class, ['get', 'wpQuery', 'timber']],
        'Terms core' => [TermsBuilder::class, ['taxonomy', 'objectIds', 'hideEmpty', 'slug', 'name', 'fields', 'include', 'exclude', 'excludeTree', 'parent', 'childOf', 'childless', 'search']],
        'Terms pagination / ordering' => [TermsBuilder::class, ['limit', 'offset', 'page', 'orderBy']],
        'Terms meta query' => [TermsBuilder::class, ['whereMeta', 'orWhereMeta']],
        'Terms conditional & hooks' => [TermsBuilder::class, ['when', 'unless', 'tap']],
        'Terms macros' => [TermsBuilder::class, ['macro', 'hasMacro', 'flushMacros']],
        'Terms escape hatch' => [TermsBuilder::class, ['tapArgs']],
        'Terms introspection' => [TermsBuilder::class, ['toArgs', 'explain']],
        'Terms terminal' => [TermsBuilder::class, ['get', 'timber']],
    ],
];
