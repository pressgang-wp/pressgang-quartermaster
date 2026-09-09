# Quartermaster Agent Guide

## ⚓ What Quartermaster Is
Quartermaster is a fluent, args-first builder for `WP_Query`. It helps you compose query intent in small explicit steps, but the output is always a plain WordPress args array via `toArgs()`, with WordPress-native behaviour under the hood.

## 🧭 Design Rules (Non-Negotiables)
- `Quartermaster::prepare()` starts empty, or only seeds `post_type` when a post type is passed.
- No defaults, no side effects, no global state mutation.
- No query-var reads unless explicitly opted into via `bindQueryVars()`.
- Methods map directly to documented `WP_Query` arguments.
  - Docs: https://developer.wordpress.org/reference/classes/wp_query/#parameters
- Avoid magic. Query state must stay inspectable through `toArgs()` and `explain()`.

## 🛠️ Canonical Usage Patterns
Basic query:

```php
$args = Quartermaster::prepare('event')
    ->status('publish')
    ->toArgs();
```

Pagination:

```php
$args = Quartermaster::prepare('post')
    ->paged(12, 2)
    ->toArgs();
```

Limit (top N without pagination):

```php
$args = Quartermaster::prepare('event')
    ->limit(3)
    ->toArgs();
```

Fetch all:

```php
$args = Quartermaster::prepare('post')
    ->all()
    ->toArgs();
```

Ordering:

```php
$args = Quartermaster::prepare('event')
    ->orderByDesc('date')
    ->orderByMetaAsc('start', 'DATE')
    ->toArgs();
```

`all()` also sets `nopaging = true` and removes `paged`; it is not an exact
argument-level synonym for `limit(-1)`. Use it for deliberately unpaginated post
queries, not term queries.

Meta queries:

```php
$args = Quartermaster::prepare('event')
    ->whereMeta('featured', 1)
    ->orWhereMeta('priority', 'high')
    ->whereMetaDate('start', '>=')
    ->toArgs();
```

Meta negation (excludes rows where key equals value **or** key was never saved):

```php
$args = Quartermaster::prepare('post')
    ->whereMetaNot('hide_from_listing', '1')
    ->toArgs();
```

Serialised meta field matching (e.g. ACF relationship/checkbox fields):

```php
$args = Quartermaster::prepare('post')
    ->whereMetaLikeAny('related_topics', [15, 22])
    ->toArgs();
```

For a single relationship ID, use `whereMetaLikeAny('projects', [$post->ID])`.
Pass raw values, not strings already wrapped in quotes. This matches serialized
string values, not arbitrary serialized data. An empty array is a no-op; retain
an explicit empty-selection guard when no selection should mean no results.

Meta existence checks:

```php
$args = Quartermaster::prepare('person')
    ->whereMetaExists('_thumbnail_id')
    ->toArgs();

$args = Quartermaster::prepare('person')
    ->whereMetaNotExists('exclude_from_people_page')
    ->toArgs();
```

Conditional queries and hooks:

```php
$q = Quartermaster::prepare('event')
    ->when($isArchive, fn ($q) =>
        $q->whereMetaDate('start', '<')->orderByMeta('start', 'DESC')
    )
    ->unless($isArchive, fn ($q) =>
        $q->whereMetaDate('start', '>=')->orderByMeta('start', 'ASC')
    )
    ->tap(fn ($q) => $debug ? $q->noFoundRows() : null);
```

Tax queries (single value or array):

```php
$args = Quartermaster::prepare('event')
    ->whereTax('event_type', 'meetup')
    ->toArgs();

$args = Quartermaster::prepare('event')
    ->whereTax('event_type', ['meetup', 'conference'])
    ->toArgs();

// Single term ID
$args = Quartermaster::prepare('event')
    ->whereTax('research_theme', $termId, 'term_id')
    ->toArgs();

// OR relation between clauses
$args = Quartermaster::prepare('post')
    ->whereTax('hit_group', $groupId, 'term_id')
    ->orWhereTax('category', $categoryId, 'term_id')
    ->toArgs();
```

Terms queries:

```php
$terms = Quartermaster::terms('category')
    ->objectIds($postId)
    ->hideEmpty(false)
    ->fields('ids')
    ->get();

$children = Quartermaster::terms('category')
    ->childOf(5)
    ->childless()
    ->slug(['rock', 'jazz'])
    ->excludeTree(12)
    ->orderBy('count', 'DESC')
    ->limit(10)
    ->get();
```

Terminals:

```php
$posts = Quartermaster::prepare('post')->get();               // posts array
$terms = Quartermaster::terms('category')->get();              // terms array
$query = Quartermaster::prepare('post')->wpQuery();            // full WP_Query object
$timber = Quartermaster::prepare('post')->timber();            // Timber PostQuery (runtime-guarded)
$timberTerms = Quartermaster::terms('category')->timber();     // Timber terms (runtime-guarded)
```

The term `timber()` adapter must fetch through WordPress `get_terms()` and
convert individual `WP_Term` objects. Preserve result filters, ordering, keys,
empty arrays and Timber class mapping. Keep scalar field projections unchanged;
never use `Timber::get_terms([])` to convert an empty result. Verify those
contracts with `tests/integration/timber-terms.php` in a WordPress/Timber runtime.

Prefer fluent helpers where their semantics match. Explicit seed arguments or
`tapArgs()` remain appropriate when a helper would discard an empty constraint
or change query behaviour. Keep queries separate from mapping and cache
assignment when nesting makes a getter hard to read.

## 🧷 Query Var Binding Guidance
Map form:

```php
$q = Quartermaster::prepare('route')->bindQueryVars([
    'shape' => Bind::tax('route_shape'),
    'orderby' => Bind::orderBy('date', 'DESC', ['title' => 'ASC']),
]);
```

Binder form:

```php
$q = Quartermaster::prepare('route')->bindQueryVars(fn (Binder $b) => $b
    ->tax('shape', 'route_shape')
    ->orderBy('orderby', 'date', 'DESC', ['title' => 'ASC'])
);
```

`Bind::orderBy()` reads an `orderby` value from the query var, falls back to `$default` when empty, and resolves the sort direction from `$overrides` (keyed by orderby value) or `$defaultOrder`.

- Binding is opt-in only.
- Quartermaster does not infer WordPress query-var semantics. Bindings still build explicit `tax_query` / `meta_query` / other args.
- Search builders own sanitization; bindings delegate to them. Avoid duplicate sanitization in surrounding code.

## 🔌 Macros
Macros are for project-specific fluent sugar. They should call existing builder methods, not mutate args directly. Macro invocations appear in `explain()` as `macro:<name>`.

```php
Quartermaster::macro('orderByMenuOrder', function (string $dir = 'ASC') {
    return $this->orderBy('menu_order', $dir);
});
```

Both `Quartermaster` and `TermsBuilder` support macros independently. Use `flushMacros()` in test teardown.

## 🧱 How to Add a New Fluent Method (Checklist)
- Add the method in the right Concern (or create a new Concern only when justified).
- Method signature must return `self`.
- Add a doc block with:
  - what WordPress args are set
  - limitations and edge cases
  - official WordPress docs link
  - explicit `@param` and `@return`
  - `Sets: <comma-separated wp_query keys>` line
- Add `record(...)` call for explainability.
- Add tests for:
  - minimal args produced
  - precedence/interaction behaviour
  - warning fallback behaviour for invalid inputs (if relevant)
- Update README method index.
- Run:
  - `find src tests -name '*.php' -print0 | xargs -0 -n1 php -l`
  - `vendor/bin/phpunit -c phpunit.xml.dist --testdox`

## 🧪 Testing & Debugging
- Use `toArgs()` to inspect the final `WP_Query` payload.
- Use `explain()` to inspect args, applied calls, binding summaries, and warnings.
- Warnings are advisory for debuggability in v0.x, not exceptions.

## 🚫 Non-Goals
- No ORM or ActiveRecord model layer.
- No smart defaults inferred from globals.
- No query engine abstraction beyond `WP_Query`.

## 📦 Where to Look
- `src/Quartermaster.php`
- `src/Concerns/*`
- `src/Bindings/*`
- `src/Adapters/*`
- `tests/*`
- `README.md`



### Match either ACF relationship field

```php
$query = Quartermaster::posts('research-project')
    ->whereMetaLikeAny('other_arc_staff', [$post->ID])
    ->orWhereMetaLikeAny('arc_lead', [$post->ID]);
```

Pass raw IDs; the helpers add the serialized-string quotes. Empty arrays add no
constraint and leave the relation unchanged. `orWhereMetaLikeAny()` follows
`orWhereMeta()`: it forces the **root** meta relation to OR, including other
existing meta clauses; subsequent `whereMeta()` calls retain that OR. For
`required AND (relationship A OR relationship B)`, use an explicitly nested
`meta_query` seed instead. Post type, pagination and taxonomy constraints are
unaffected. Fixed argument clauses belong in seed arrays; reserve `tapArgs()`
for transformations of existing query arguments.


### Explicit empty searches and required taxonomy constraints

```php
$query = Quartermaster::posts('publication')
    ->relevanssi($search, allowEmpty: true)
    ->paged(paged: $page);

$staff = Quartermaster::posts('staff-member')
    ->whereTax('research-team', $teamIds, 'term_id', allowEmpty: true);
```

These options are explicit: existing calls retain their behaviour.
`relevanssi(..., allowEmpty: true)` sets the sanitized search even when empty,
including replacing an earlier search, and enables Relevanssi. Null leaves the
search unchanged. This sets query arguments; it does not install the plugin or
manage its index. The flag is enabled whenever an `s` argument is present.
`Binder::relevanssi()` and `Bind::relevanssi()` accept the same option; missing or
null binding values remain skipped unless a default is supplied, while an explicitly empty value is applied.

`whereTax()` and `orWhereTax()` accept `allowEmpty: true` to retain a normalized
empty clause, including null input. WordPress operator semantics still apply:
empty `IN` matches no posts, while empty `NOT IN` excludes nothing. This is a
required relationship constraint, not a blanket “no results” switch. Existing
optional filters continue to ignore empty inputs. Root relation behaviour is
unchanged. Preserve form state separately and check query parity during adoption.


### Term metadata ordering

Use the same metadata-ordering vocabulary on post and term queries:

```php
$terms = Quartermaster::terms('research-theme')
    ->hideEmpty(false)
    ->orderByMetaNumeric('sort_order')
    ->timber();
```

`TermsBuilder::orderByMeta($key, $order = 'ASC', $metaType = 'CHAR')` sets
`meta_key`, `meta_type`, `orderby=meta_value` and direction.
`orderByMetaNumeric($key, $order = 'ASC')` uses `meta_value_num`. These follow
WordPress's metadata joins: terms without that key are excluded. They preserve
other query constraints and continue through the normal WordPress-backed term
terminal, including final ordering filters. Invalid directions use the existing
orderBy warning and ASC fallback. Prefer these helpers over raw meta_key seeds.


### Search binding defaults and input handling

```php
$query = Quartermaster::posts('research-project')
    ->bindQueryVars(function (Binder $bindings): void {
        $bindings->relevanssi('project-search', allowEmpty: true, default: '');
    });
```

`Bind::search()` / `Binder::search()` accept `?string $default = null`;
`Bind::relevanssi()` / `Binder::relevanssi()` accept it after `allowEmpty`.
A default applies only to missing/null values. An explicit empty string never
uses the default. Without a default, missing/null values leave the query unchanged.
Use `allowEmpty: true, default: ''` to enable Relevanssi even without a search term.
This deliberately opts into an empty search; it does not install or index Relevanssi.

Scalar inputs are converted to strings; arrays, objects and resources are skipped
without applying the default or replacing an existing search. Sanitization belongs
to the search builder: `sanitize_text_field()` in WordPress, trimming otherwise.
Do not duplicate it around bindings. Bindings do not execute queries or escape
HTML output; keep contextual escaping in templates.

Never automatically URL-decode query variables. PHP already decodes GET values;
decoding again corrupts literal plus signs such as `C++`. WordPress sanitization
strips percent-encoded characters; Quartermaster retains that established behaviour.
See [PHP's urldecode guidance](https://www.php.net/manual/en/function.urldecode.php)
and [WordPress sanitization](https://developer.wordpress.org/reference/functions/sanitize_text_field/).
Use custom preparation only for a documented source encoding, before sanitization.
