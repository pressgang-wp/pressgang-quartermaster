# ⚓ Quartermaster

**Quartermaster** is a fluent, args-first builder for `WP_Query`.

It helps you build complex query arrays in readable, composable steps, while staying **100% WordPress-native under the hood**. It ships as a standalone package in the `pressgang-wp` ecosystem, but it does **not** depend on the PressGang theme framework.

Think of it as a reliable quartermaster for your query cargo: **you decide what goes aboard, nothing gets smuggled in**. 🧭

---

## 📦 Install

```bash
composer require pressgang-wp/quartermaster
```

Requirements: PHP 8.3+.

---

## 🗺️ Quick Reference Method Index

| Area | Methods |
| --- | --- |
| Bootstrap | `posts()`, `terms()`, `prepare()` (compatibility alias) |
| Core post constraints | `postType()`, `status()`, `whereId()`, `whereInIds()`, `excludeIds()`, `whereParent()`, `whereParentIn()` |
| Author constraints | `whereAuthor()`, `whereAuthorIn()`, `whereAuthorNotIn()` |
| Pagination / search | `paged()`, `all()` (fetch all: `posts_per_page=-1`, `nopaging=true`), `search()` |
| Query-var binding | `bindQueryVars()` |
| Ordering | `orderBy()`, `orderByAsc()`, `orderByDesc()`, `orderByMeta()`, `orderByMetaAsc()`, `orderByMetaDesc()`, `orderByMetaNumeric()`, `orderByMetaNumericAsc()`, `orderByMetaNumericDesc()` |
| Meta query | `whereMeta()`, `orWhereMeta()`, `whereMetaDate()` |
| Tax query | `whereTax()` |
| Date query | `whereDate()`, `whereDateAfter()`, `whereDateBefore()` |
| Query-shaping flags | `idsOnly()`, `noFoundRows()`, `withMetaCache()`, `withTermCache()` |
| Conditional & hooks | `when()`, `unless()`, `tap()` |
| Escape hatch | `tapArgs()` |
| Introspection | `toArgs()`, `explain()` |
| Terminals | `get()`, `wpQuery()`, `timber()` |
| Terms core | `taxonomy()`, `objectIds()`, `hideEmpty()`, `slug()`, `name()`, `fields()`, `include()`, `exclude()`, `excludeTree()`, `parent()`, `childOf()`, `childless()`, `search()` |
| Terms pagination / ordering | `limit()`, `offset()`, `page()`, `orderBy()` |
| Terms meta query | `whereMeta()`, `orWhereMeta()` |
| Terms terminal | `get()`, `timber()` |

---

## 🤔 Why Fluent?

`WP_Query` arrays are powerful, but as they grow they become harder to scan, review, and refactor.

Quartermaster gives you:

- ✨ Better readability — query intent is expressed step-by-step
- 🧩 Better composability — add or remove clauses without rewriting a large array
- 🛡️ Better safety — methods are explicit about which WP args they set
- 🔍 Better debugging — inspect exact output with `toArgs()` and `explain()`

You still end up with **plain WordPress args**.  
No ORM. No hidden query engine. No lock-in. Just well-organised cargo. ⚓

Sometimes raw `WP_Query` is fine — if your query is short and static, use it.  
Quartermaster shines when queries evolve, branch, or need to be composed without losing your bearings. 🧭

---

## 🧠 Design Philosophy

Quartermaster is intentionally light-touch:

- 🧱 WordPress-native — every fluent method maps directly to real `WP_Query` keys
- 🫙 Zero side effects by default — `Quartermaster::posts()->toArgs()` is empty
- 🎯 Opt-in only — nothing changes unless you call a method
- 🔌 Loosely coupled — no mutation of WordPress internals, no global state changes
- 🌲 Timber-agnostic core — Timber support is optional and runtime-guarded
- 🧭 Explicit over magic — sharp WP edges are documented, not hidden

Steady hands on the wheel, predictable seas ahead. 🚢

```php
Quartermaster::posts()->toArgs(); // []
```

---

## 🚫 Non-Goals (Read Before Boarding)

Quartermaster deliberately does **not** aim to:

- Replace `WP_Query` or abstract it away
- Act as an ORM or ActiveRecord layer
- Hide WordPress limitations (e.g. tax/meta OR logic)
- Automatically infer defaults or “best practices”
- Replace WordPress term query APIs

If WordPress requires a specific argument shape, **Quartermaster expects you to be explicit**.  
No fog, no illusions, no siren songs. 🧜‍♀️

---

## 🚀 Quick Start

`posts('event')` is a convenience seed only. It only sets `post_type` and does not infer any other query args.

```php
Quartermaster::posts('event');

// is equivalent to

Quartermaster::posts()->postType('event');
```

`prepare()` remains available as a low-level backwards-compatible alias.

```php
use PressGang\Quartermaster\Quartermaster;

$args = Quartermaster::posts()
    ->postType('event')
    ->status('publish')
    ->paged(10)
    ->orderByMeta('start', 'ASC')
    ->search(get_query_var('s'))
    ->toArgs();
```

Run the query and get posts:

```php
$posts = Quartermaster::posts()
    ->postType('event')
    ->status('publish')
    ->get();
```

When you need the full `WP_Query` object (pagination metadata, found rows, loop state):

```php
$query = Quartermaster::posts()
    ->postType('event')
    ->status('publish')
    ->wpQuery();

$posts = $query->posts;
$total = $query->found_posts;
```

## 🌿 Terms Quick Start

```php
use PressGang\Quartermaster\Quartermaster;

$terms = Quartermaster::terms('category')
    ->hideEmpty()
    ->orderBy('name')
    ->limit(20)
    ->get();
```

Filter by slug, get just IDs, or scope to a specific post:

```php
// Terms attached to a specific post
$tags = Quartermaster::terms('post_tag')
    ->objectIds($post->ID)
    ->get();

// Leaf categories only (no children), return IDs
$leafIds = Quartermaster::terms('category')
    ->childless()
    ->fields('ids')
    ->get();

// Find terms by slug
$terms = Quartermaster::terms('genre')
    ->slug(['rock', 'jazz'])
    ->hideEmpty(false)
    ->get();

// All descendants of a parent term
$children = Quartermaster::terms('category')
    ->childOf(5)
    ->excludeTree(12)
    ->get();

// Get Timber term objects (runtime-guarded)
$timberTerms = Quartermaster::terms('category')
    ->hideEmpty()
    ->orderBy('name')
    ->timber();
```

Inspect generated args:

```php
$args = Quartermaster::terms('category')
    ->hideEmpty(false)
    ->whereMeta('featured', 1)
    ->toArgs();
```

## 🔗 Binding Query Vars (Two Styles)

Nothing reads query vars unless you explicitly call `bindQueryVars()`.

Map style with `Bind::*`:

```php
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Quartermaster;

$q = Quartermaster::posts('route')->bindQueryVars([
    'paged' => Bind::paged(),
    'shape' => Bind::tax('route_shape'),
    'difficulty' => Bind::tax('route_difficulty'),
    'min_distance' => Bind::metaNum('distance_miles', '>='),
    'max_distance' => Bind::metaNum('distance_miles', '<='),
    'search' => Bind::search(),
]);
```

Fluent binder style with `Binder`:

```php
use PressGang\Quartermaster\Bindings\Binder;
use PressGang\Quartermaster\Quartermaster;

$q = Quartermaster::posts('route')->bindQueryVars(function (Binder $b): void {
    $b->paged();
    $b->tax('district'); // district -> district
    $b->tax('shape', 'route_shape'); // shape -> route_shape
    $b->tax('difficulty', 'route_difficulty');
    $b->metaNum('min_distance')->to('distance_miles', '>=');
    $b->metaNum('max_distance')->to('distance_miles', '<=');
    $b->search('search');
});
```

If no taxonomy is provided, Binder assumes the taxonomy name matches the query var key.

Both styles are explicit and compile to the same binding map. No smuggling, no hidden defaults.

---

## 🗓️ Common Pattern: Meta Date vs Today

Filtering by a meta date (e.g. upcoming vs past events) is a very common WordPress pattern.

```php
$isArchive = isset($_GET['archive']);

$q = Quartermaster::posts()
    ->postType('event')
    ->status('publish')
    ->whereMetaDate('start', $isArchive ? '<' : '>=')
    ->orderByMeta('start', $isArchive ? 'DESC' : 'ASC');
```

This keeps intent explicit:

- `whereMetaDate(...)` adds a `meta_query` DATE clause
- `orderByMeta(...)` controls ordering separately

No hidden assumptions. No barnacles. ⚓

---

## 🔀 Conditional Queries & Hooks

`when()`, `unless()`, and `tap()` keep fluent chains readable without introducing magic or hidden state. None of them read globals or add defaults.

**`when()`** — runs a closure when the condition is true:

```php
$q = Quartermaster::posts('event')
    ->when($isArchive, fn ($q) =>
        $q->whereMetaDate('start', '<')->orderByMeta('start', 'DESC')
    )
    ->when(! $isArchive, fn ($q) =>
        $q->whereMetaDate('start', '>=')->orderByMeta('start', 'ASC')
    );
```

Or with an else clause:

```php
$q = Quartermaster::posts('event')
    ->when(
        $isArchive,
        fn ($q) => $q->orderBy('date', 'DESC'),
        fn ($q) => $q->orderBy('date', 'ASC'),
    );
```

**`unless()`** — inverse of `when()` (`unless($x)` is `when(!$x)`):

```php
$q = Quartermaster::posts('event')
    ->unless($isArchive, fn ($q) =>
        $q->whereMetaDate('start', '>=')->orderByMeta('start', 'ASC')
    );
```

**`tap()`** — always runs a closure, for builder-level logic without breaking the chain:

```php
$q = Quartermaster::posts('event')
    ->tap(function ($q) use ($debug) {
        if ($debug) {
            $q->noFoundRows();
        }
    })
    ->status('publish');
```

All three are recorded in `explain()` for debuggability. No magic, no hidden state. ⚓

---

## 🌲 Optional Timber Terminal

```php
$posts = Quartermaster::posts()
    ->postType('event')
    ->status('publish')
    ->timber();
```

If Timber is unavailable, Quartermaster throws a **clear runtime exception** rather than hard-coupling Timber into core.

---

## 🔍 Debugging & Introspection

Ordering direction is explicit: Quartermaster accepts only `ASC`/`DESC`; invalid values are normalized to method defaults and surfaced in `explain()` warnings.

Inspect generated args:

```php
$args = Quartermaster::posts()
    ->postType('event')
    ->toArgs();
```

Inspect args plus applied calls and warnings:

```php
$explain = Quartermaster::posts()
    ->orderBy('meta_value')
    ->explain();
```

Perfect for reviews, debugging, and keeping junior crew out of trouble. 🧭

Smooth seas and predictable queries.  
Happy sailing. ⚓🚢
