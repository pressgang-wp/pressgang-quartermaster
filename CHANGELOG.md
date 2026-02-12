# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- Added `whereMetaNot()` to exclude posts where a meta key equals a value or was never saved (nested `!= OR NOT EXISTS` sub-group).
- Added `whereMetaExists()` and `whereMetaNotExists()` for meta key presence/absence checks.
- Added macro system (`macro()`, `hasMacro()`, `flushMacros()`) to `Quartermaster` and `TermsBuilder`.
- Added `when()`, `unless()`, and `tap()` fluent helpers to `Quartermaster` and `TermsBuilder`.
- Added `objectIds()`, `slug()`, `name()`, `fields()`, `excludeTree()`, `childOf()`, and `childless()` fluent methods to `TermsBuilder`.
- Added `timber()` terminal to `TermsBuilder` via `Timber::get_terms()` (runtime-guarded).
- Added `get()` terminal to `Quartermaster` posts builder for symmetry with `TermsBuilder`; returns the posts array directly.
- Renamed internal `HasArgs::get()` to `HasArgs::getArg()` to free `get()` for terminal use.
- Added `toArray()` terminal to `Quartermaster`; prefers Timber when available, falls back to WP_Query.

## [0.1.0] - 2026-02-08

- Initial public release of `pressgang/quartermaster`.
- Added fluent, WP-native args builder with zero-side-effects defaults.
- Added explicit query axes for post, author, meta, tax, and date constraints.
- Added explicit pagination, ordering, performance flags, and search helpers.
- Added optional guarded Timber terminal.
- Added debugging/introspection via `toArgs()` and `explain()`.
- Added PHPUnit smoke coverage and CI workflow scaffold.
