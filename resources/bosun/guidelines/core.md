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
