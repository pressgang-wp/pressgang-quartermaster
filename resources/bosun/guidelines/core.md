## Quartermaster (query builder)

Build WP_Query args with the fluent, args-first builder — never hand-rolled
arrays in controllers or traits:

- Entry points: `Quartermaster::posts( $type )`, `Quartermaster::terms( $tax )`.
- Common chain: `->status('publish')->whereMeta(...)->whereTax(...)
  ->orderByMeta(...)->paged()` with terminals `->timber()` (Timber PostQuery),
  `->toArray()` (Timber posts array — use for truthiness-checked context
  values; PostQuery objects are always truthy), `->toArgs()` (plain args,
  e.g. for Routes), `->get()`, `->wpQuery()`.
- Optional filters pass through directly: `->whereTax('topic', $topic ?: null)`
  — null and empty terms leave the builder unchanged; `excludeIds([])` is a
  no-op. Use `when()` for genuine conditional logic, not optional values.
- `orWhereMeta()` / `orWhereTax()` switch the clause relation to OR.
- Inspect with `->toArgs()` and `->explain()`. Consult
  `vendor/pressgang-wp/quartermaster/docs/api-index.json` for the full
  method surface.
