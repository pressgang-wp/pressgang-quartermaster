# ⚓ Quartermaster

[![Latest Stable Version](https://poser.pugx.org/pressgang-wp/quartermaster/v/stable)](https://packagist.org/packages/pressgang-wp/quartermaster)
[![Build Status](https://github.com/pressgang-wp/pressgang-quartermaster/actions/workflows/ci.yml/badge.svg)](https://github.com/pressgang-wp/pressgang-quartermaster/actions/workflows/ci.yml)
[![Total Downloads](https://poser.pugx.org/pressgang-wp/quartermaster/downloads)](https://packagist.org/packages/pressgang-wp/quartermaster)
[![License](https://poser.pugx.org/pressgang-wp/quartermaster/license)](https://packagist.org/packages/pressgang-wp/quartermaster)

**Quartermaster** is a fluent **WordPress query builder** — an args-first, chainable interface for `WP_Query` and `WP_Term_Query`.

It turns sprawling `meta_query`, `tax_query`, and `date_query` arrays into readable, composable steps, while staying **100% WordPress-native under the hood**. You get Laravel-style fluent chaining with zero ORM: every method maps to a real `WP_Query` arg. It ships as a standalone Composer package (PHP 8.3+) in the `pressgang-wp` ecosystem, but it does **not** depend on the PressGang theme framework.

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
| Pagination / search | `paged()`, `limit()`, `all()` (fetch all: `posts_per_page=-1`, `nopaging=true`), `search()` |
| Query-var binding | `bindQueryVars()`, `Bind::paged()`, `Bind::tax()`, `Bind::orderBy()`, `Bind::metaNum()`, `Bind::search()` |
| Ordering | `orderBy()`, `orderByAsc()`, `orderByDesc()`, `orderByMeta()`, `orderByMetaAsc()`, `orderByMetaDesc()`, `orderByMetaNumeric()`, `orderByMetaNumericAsc()`, `orderByMetaNumericDesc()` |
| Meta query | `whereMeta()`, `orWhereMeta()`, `whereMetaNot()`, `whereMetaLikeAny()`, `orWhereMetaLikeAny()`, `whereMetaDate()`, `whereMetaExists()`, `whereMetaNotExists()` |
| Tax query | `whereTax()`, `orWhereTax()` |
| Date query | `whereDate()`, `whereDateAfter()`, `whereDateBefore()` |
| Query-shaping flags | `idsOnly()`, `noFoundRows()`, `withMetaCache()`, `withTermCache()` |
| Conditional & hooks | `when()`, `unless()`, `tap()` |
| Macros | `macro()`, `hasMacro()`, `flushMacros()` |
| Escape hatch | `tapArgs()` |
| Introspection | `toArgs()`, `explain()` |
| Terminals | `get()`, `toArray()`, `wpQuery()`, `timber()`, `applyTo()` |
| Terms core | `taxonomy()`, `objectIds()`, `hideEmpty()`, `slug()`, `name()`, `fields()`, `include()`, `exclude()`, `excludeTree()`, `parent()`, `childOf()`, `childless()`, `search()` |
| Terms pagination / ordering | `limit()`, `offset()`, `page()`, `orderBy()` |
| Terms meta query | `whereMeta()`, `orWhereMeta()` |
| Terms terminal | `get()`, `timber()` |

---

## 🤔 Why a Fluent WordPress Query Builder?

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

Taxonomy clauses combine with `AND` by default; use `orWhereTax()` for `OR`:

```php
$args = Quartermaster::posts('post')
    ->whereTax('hit_group', $groupId, 'term_id')
    ->orWhereTax('category', $categoryId, 'term_id')
    ->toArgs();

// tax_query => [ relation => OR, [hit_group clause], [category clause] ]
```

## Fetching all posts and matching ACF relationships

Use `all()` when the query should return every matching post:

```php
$projects = Quartermaster::posts('research-project')->all()->toArray();
```

`all()` sets `posts_per_page` to `-1`, sets `nopaging` to `true`, and removes
`paged`. `limit(-1)` only sets the limit. Check pagination requirements before
replacing an existing chain. This helper belongs to the posts builder, not the
terms builder.

Use `whereMetaLikeAny()` for ACF relationships stored as serialized string IDs:

```php
$query = Quartermaster::posts('publication')
    ->whereMetaLikeAny('projects', [$post->ID]);
```

Pass unquoted values. The helper adds the quotes and an OR group for multiple
values. It is not a general substring search or a matcher for serialized integer
values. An empty input array adds no constraint; guard an empty required
selection rather than accidentally returning every post.

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

The term `timber()` terminal fetches through WordPress `get_terms()` before
converting each term with Timber. This preserves result filters, plugin ordering
and empty results while applying Timber's term class mapping. Explicit scalar
field projections retain their values and keys. Invalid taxonomies and count
results raise `RuntimeException`; this terminal returns lists.

This behaviour requires the term-filter fix in commit `49d5787` or a descendant.
On older installations, verify plugin ordering before replacing a WordPress
fetch followed by individual term conversion. Do not pass an empty list to
`Timber::get_terms()` as a conversion shortcut; Timber 2.5.1 treats it as a query.

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
    'orderby' => Bind::orderBy('date', 'DESC', ['title' => 'ASC']),
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
    $b->orderBy('orderby', 'date', 'DESC', ['title' => 'ASC']);
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

## 🔌 Macros (Project-Level Sugar)

Macros let you register project-specific fluent methods without bloating the core API. They are opt-in, not part of core — use them for patterns that repeat across your project.

```php
Quartermaster::macro('orderByMenuOrder', function (string $dir = 'ASC') {
    return $this->orderBy('menu_order', $dir);
});

$posts = Quartermaster::posts('page')
    ->orderByMenuOrder()
    ->status('publish')
    ->get();
```

Macros should call existing Quartermaster methods — avoid mutating internal args directly. Macro invocations are recorded in `explain()` as `macro:<name>` for debuggability.

Register macros in your theme's `functions.php` or a service provider. Both builders (`Quartermaster` and `TermsBuilder`) support macros independently.

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

## 🪝 Query Hooks (`pre_get_posts`)

`applyTo()` modifies an existing `WP_Query` in place instead of creating a new one — designed for WordPress `pre_get_posts` hooks:

```php
add_action('pre_get_posts', function (WP_Query $query): void {
    if (! $query->is_main_query() || is_admin()) {
        return;
    }

    Quartermaster::posts('product')
        ->whereTax('product_visibility', ['exclude-from-catalog'], 'name', 'NOT IN')
        ->whereMetaExists('_price')
        ->applyTo($query);
});
```

When multiple hooks call `applyTo()`, clause arrays (`tax_query`, `meta_query`, `date_query`) are **merged** with existing clauses — not overwritten — so hooks compose safely.

`applyTo()` is a **void terminal**: it does not return the builder. If you need to inspect the applied args, hold a reference to the builder and call `explain()` separately.

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
