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

Meta queries:

```php
$args = Quartermaster::prepare('event')
    ->whereMeta('featured', 1)
    ->orWhereMeta('priority', 'high')
    ->whereMetaDate('start', '>=')
    ->toArgs();
```

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

## 🧷 Query Var Binding Guidance
Map form:

```php
$q = Quartermaster::prepare('route')->bindQueryVars([
    'shape' => Bind::tax('route_shape'),
]);
```

Binder form:

```php
$q = Quartermaster::prepare('route')->bindQueryVars(fn (Binder $b) => $b->tax('shape', 'route_shape'));
```

- Binding is opt-in only.
- Quartermaster does not infer WordPress query-var semantics. Bindings still build explicit `tax_query` / `meta_query` / other args.
- `search()` sanitises values and `Bind::search()` sanitises values; avoid blind double-sanitisation in surrounding code.

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

