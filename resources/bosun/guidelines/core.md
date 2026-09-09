## Quartermaster (query builder)

Prefer the fluent, args-first builder for WordPress queries. Use explicit seed
args or `tapArgs()` when no helper preserves the required semantics:

- Entry points: `Quartermaster::posts( $type )`, `Quartermaster::terms( $tax )`.
- Common chain: `->status('publish')->whereMeta(...)->whereTax(...)
  ->orderByMeta(...)->paged()` with terminals `->timber()` (Timber PostQuery),
  `->toArray()` (Timber posts array — use for truthiness-checked context
  values; PostQuery objects are always truthy), `->toArgs()` (plain args,
  e.g. for Routes), `->get()`, `->wpQuery()`.
- Use post-builder `all()` for all results: it sets `posts_per_page=-1`, sets
  `nopaging=true` and removes `paged`. `limit(-1)` only changes the limit; check
  pagination before replacing it. The terms builder has no `all()` method.
- Use `whereMetaLikeAny('projects', [$post->ID])` for ACF serialized string IDs;
  pass raw values, not manually quoted strings. Multiple IDs form an OR group.
  Empty arrays add no constraint, so guard required empty selections separately.
- Term-builder `timber()` returns Timber terms after WordPress result filters;
  keep plugin ordering, empty results and class mapping intact. This requires
  the term-filter fix (49d5787 or later). With older versions, compare ordering
  before replacing explicit WordPress fetch/mapping. Do not convert an empty
  result with `Timber::get_terms([])`: it can trigger a new query.
- Keep query construction, mapping and cache assignment readable as separate
  steps where combining them creates nested expressions.
- Optional filters pass through directly: `->whereTax('topic', $topic ?: null)`
  — null and empty terms leave the builder unchanged; `excludeIds([])` is a
  no-op. Use `when()` for genuine conditional logic, not optional values.
- `orWhereMeta()` / `orWhereTax()` switch the clause relation to OR.
- Inspect with `->toArgs()` and `->explain()`. Consult
  `vendor/pressgang-wp/quartermaster/docs/api-index.json` for the full
  method surface.


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
