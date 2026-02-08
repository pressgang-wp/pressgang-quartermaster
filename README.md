# ⚓ Quartermaster

**Quartermaster** is a fluent, args-first builder for `WP_Query`.

It helps you build complex query arrays in readable, composable steps, while staying **100% WordPress-native under the hood**. It ships as a standalone package in the `pressgang-wp` ecosystem, but it does **not** depend on the PressGang theme framework.

Think of it as a reliable quartermaster for your query cargo: **you decide what goes aboard, nothing gets smuggled in**. 🧭

---

## 📦 Install

```bash
composer require pressgang/quartermaster
```

Requirements: PHP 8.3+.

---

## 🗺️ Quick Reference Method Index

| Area | Methods |
| --- | --- |
| Bootstrap | `prepare()` |
| Core post constraints | `postType()`, `status()`, `whereId()`, `whereInIds()`, `excludeIds()`, `whereParent()`, `whereParentIn()` |
| Author constraints | `whereAuthor()`, `whereAuthorIn()`, `whereAuthorNotIn()` |
| Pagination / search | `paged()`, `search()` |
| Query-var binding | `bindQueryVars()` |
| Ordering | `orderBy()`, `orderByMeta()`, `orderByMetaNumeric()` |
| Meta query | `whereMeta()`, `orWhereMeta()`, `whereMetaDate()` |
| Tax query | `whereTax()` |
| Date query | `whereDate()`, `whereDateAfter()`, `whereDateBefore()` |
| Query-shaping flags | `idsOnly()`, `noFoundRows()`, `withMetaCache()`, `withTermCache()` |
| Escape hatch | `tapArgs()` |
| Introspection | `toArgs()`, `explain()` |
| Terminals | `wpQuery()`, `timber()` |

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
- 🫙 Zero side effects by default — `Quartermaster::prepare()->toArgs()` is empty
- 🎯 Opt-in only — nothing changes unless you call a method
- 🔌 Loosely coupled — no mutation of WordPress internals, no global state changes
- 🌲 Timber-agnostic core — Timber support is optional and runtime-guarded
- 🧭 Explicit over magic — sharp WP edges are documented, not hidden

Steady hands on the wheel, predictable seas ahead. 🚢

```php
Quartermaster::prepare()->toArgs(); // []
```

---

## 🚫 Non-Goals (Read Before Boarding)

Quartermaster deliberately does **not** aim to:

- Replace `WP_Query` or abstract it away
- Act as an ORM or ActiveRecord layer
- Hide WordPress limitations (e.g. tax/meta OR logic)
- Automatically infer defaults or “best practices”
- Query users, terms, or comments (yet)

If WordPress requires a specific argument shape, **Quartermaster expects you to be explicit**.  
No fog, no illusions, no siren songs. 🧜‍♀️

---

## 🚀 Quick Start

`prepare('event')` is a convenience seed only. It only sets `post_type` and does not infer any other query args.

```php
Quartermaster::prepare('event');

// is equivalent to

Quartermaster::prepare()->postType('event');
```

```php
use PressGang\Quartermaster\Quartermaster;

$args = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->paged(10)
    ->orderByMeta('start', 'ASC')
    ->search(get_query_var('s'))
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

## 🔗 Binding Query Vars (Two Styles)

Nothing reads query vars unless you explicitly call `bindQueryVars()`.

Map style with `Bind::*`:

```php
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Quartermaster;

$q = Quartermaster::prepare('route')->bindQueryVars([
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

$q = Quartermaster::prepare('route')->bindQueryVars(function (Binder $b): void {
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

$q = Quartermaster::prepare()
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

## 🌲 Optional Timber Terminal

```php
$posts = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->timber();
```

If Timber is unavailable, Quartermaster throws a **clear runtime exception** rather than hard-coupling Timber into core.

---

## 🔍 Debugging & Introspection

Inspect generated args:

```php
$args = Quartermaster::prepare()
    ->postType('event')
    ->toArgs();
```

Inspect args plus applied calls and warnings:

```php
$explain = Quartermaster::prepare()
    ->orderBy('meta_value')
    ->explain();
```

Perfect for reviews, debugging, and keeping junior crew out of trouble. 🧭

Smooth seas and predictable queries.  
Happy sailing. ⚓🚢
