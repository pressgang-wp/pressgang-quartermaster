# Quartermaster

Quartermaster is a fluent, args-first builder for `WP_Query`.

It helps you build complex query arrays in readable steps, while staying 100% WordPress-native under the hood. It ships as a standalone package in the `pressgang-wp` ecosystem, but it does **not** depend on the PressGang theme framework.

Think of it as a reliable quartermaster for your query cargo: you decide what goes aboard, nothing gets smuggled in. ⚓

## Install

```bash
composer require pressgang/quartermaster
```

## Why Fluent?

`WP_Query` arrays are powerful, but as they grow they become harder to scan, review, and refactor.

Quartermaster gives you:

- Better readability: query intent is expressed step-by-step.
- Better composability: add/remove clauses without rewriting a large array.
- Better safety: methods are explicit about which WP args they set.
- Better debugging: inspect exact output with `toArgs()` and `explain()`.

You still end up with plain WordPress args. No ORM, no hidden query engine, no lock-in.

## Design Philosophy

Quartermaster is intentionally light-touch:

- WordPress-native: every fluent method maps directly to real `WP_Query` keys.
- Zero side effects by default: `Quartermaster::prepare()->toArgs()` is empty.
- Opt-in only: nothing changes unless you call a method.
- Loosely coupled: no mutation of WordPress internals, no global state changes.
- Timber-agnostic core: Timber support is optional and runtime-guarded.
- Explicit over magic: sharp WP edges are documented, not hidden.

## Quick Start

```php
use PressGang\Quartermaster\Quartermaster;

$args = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->paged(10)
    ->orderByMeta('start', 'ASC')
    ->search(get_query_var('search'))
    ->toArgs();
```

Run the query with WordPress:

```php
$query = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->toArgs();

$posts = new WP_Query($query);
```

Or use the built-in terminal:

```php
$posts = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->wpQuery();
```

## Common Pattern: Meta Date vs Today

```php
$isArchive = isset($_GET['archive']);

$q = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->whereMetaDate('start', $isArchive ? '<' : '>=')
    ->orderByMeta('start', $isArchive ? 'DESC' : 'ASC');
```

This keeps filtering and ordering explicit:

- `whereMetaDate(...)` adds a `meta_query` DATE clause.
- `orderByMeta(...)` controls ordering.

## Optional Timber Terminal

```php
$posts = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->timber();
```

If Timber is unavailable, Quartermaster throws a clear runtime exception instead of hard-coupling Timber into core.

## Debugging and Introspection

Inspect generated args:

```php
$args = Quartermaster::prepare()
    ->postType('event')
    ->toArgs();
```

Inspect args + applied method log + warnings:

```php
$explain = Quartermaster::prepare()
    ->orderBy('meta_value')
    ->explain();
```

## Method Index

| Area | Methods |
| --- | --- |
| Bootstrap | `prepare()` |
| Core post constraints | `postType()`, `status()`, `whereId()`, `whereInIds()`, `excludeIds()`, `whereParent()`, `whereParentIn()` |
| Author constraints | `whereAuthor()`, `whereAuthorIn()`, `whereAuthorNotIn()` |
| Pagination/search | `paged()`, `search()` |
| Ordering | `orderBy()`, `orderByMeta()`, `orderByMetaNumeric()` |
| Meta query | `whereMeta()`, `orWhereMeta()`, `whereMetaDate()` |
| Tax query | `whereTax()` |
| Date query | `whereDate()`, `whereDateAfter()`, `whereDateBefore()` |
| Query-shaping flags | `idsOnly()`, `noFoundRows()`, `withMetaCache()`, `withTermCache()` |
| Escape hatch | `tapArgs()` |
| Introspection | `toArgs()`, `explain()` |
| Terminals | `wpQuery()`, `timber()` |

## Core Principles Recap

- Fluent API on top of native `WP_Query` args
- Light-touch and low-coupling by design
- No defaults unless explicitly requested
- Optional adapters (WordPress, Timber)
- Clear, debuggable output at every step

Smooth seas and predictable queries. 🚢

## Full Method Examples

All methods are opt-in and chainable unless noted.

### Bootstrap / terminals

```php
use PressGang\Quartermaster\Quartermaster;

// Start empty
$q = Quartermaster::prepare();

// Start with seed args
$q = Quartermaster::prepare([
    'post_type' => 'event',
]);

// Raw args
$args = $q->toArgs();

// Explain payload: args + applied calls + warnings
$debug = $q->explain();

// WP_Query terminal
$wpQuery = $q->wpQuery();

// Timber terminal (optional, guarded)
$timberQuery = $q->timber();
```

### Core post constraints

```php
$q = Quartermaster::prepare()
    ->postType('event')          // post_type
    ->status('publish')          // post_status
    ->whereId(42)                // p
    ->whereInIds([1, 2, 3])      // post__in
    ->excludeIds([9, 10])        // post__not_in
    ->whereParent(7)             // post_parent
    ->whereParentIn([7, 8]);     // post_parent__in
```

### Author constraints

```php
$q = Quartermaster::prepare()
    ->whereAuthor(3)             // author
    ->whereAuthorIn([3, 4])      // author__in
    ->whereAuthorNotIn([6, 7]);  // author__not_in
```

### Pagination and search

```php
$q = Quartermaster::prepare()
    ->paged(10, 2)               // posts_per_page + paged
    ->search('regatta');         // s
```

### Ordering

```php
$q = Quartermaster::prepare()
    ->orderBy('date', 'DESC')                 // orderby + order
    ->orderByMeta('start', 'ASC', 'DATE')     // meta_key + orderby=meta_value + order + meta_type
    ->orderByMetaNumeric('price', 'ASC');     // meta_key + orderby=meta_value_num + order
```

### Meta query

```php
$q = Quartermaster::prepare()
    ->whereMeta('start', '20260208', '>=', 'DATE')
    ->orWhereMeta('featured', '1')
    ->whereMetaDate('start', '>='); // null value => wp_date('Ymd')
```

### Tax query

```php
$q = Quartermaster::prepare()
    ->whereTax('topic', ['news', 'events'], 'slug', 'IN');
```

### Date query

```php
$q = Quartermaster::prepare()
    ->whereDate(['year' => 2026])
    ->whereDateAfter('2026-01-01', true)
    ->whereDateBefore('2026-12-31', false);
```

### Query-shaping / performance flags

```php
$q = Quartermaster::prepare()
    ->idsOnly()                  // fields = ids
    ->noFoundRows()              // no_found_rows = true
    ->withMetaCache(false)       // update_post_meta_cache
    ->withTermCache(false);      // update_post_term_cache
```

### Escape hatch

```php
$q = Quartermaster::prepare()
    ->postType('event')
    ->tapArgs(function (array $args): array {
        $args['ignore_sticky_posts'] = true;
        return $args;
    });
```
