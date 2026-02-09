# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- Added `when()`, `unless()`, and `tap()` fluent helpers to `Quartermaster` and `TermsBuilder`.
- Added `objectIds()`, `slug()`, `name()`, `fields()`, `excludeTree()`, `childOf()`, and `childless()` fluent methods to `TermsBuilder`.

## [0.1.0] - 2026-02-08

- Initial public release of `pressgang/quartermaster`.
- Added fluent, WP-native args builder with zero-side-effects defaults.
- Added explicit query axes for post, author, meta, tax, and date constraints.
- Added explicit pagination, ordering, performance flags, and search helpers.
- Added optional guarded Timber terminal.
- Added debugging/introspection via `toArgs()` and `explain()`.
- Added PHPUnit smoke coverage and CI workflow scaffold.
