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

Tax queries:

```php
$args = Quartermaster::prepare('event')
    ->whereTax('event_type', ['meetup'])
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
$query = Quartermaster::prepare('post')->wpQuery();
$timber = Quartermaster::prepare('post')->timber(); // runtime-guarded if Timber is missing
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

