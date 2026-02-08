# Quartermaster

Quartermaster is a standalone, fluent `WP_Query` args builder for PHP and WordPress projects.

It is developed in the `pressgang-wp` ecosystem, but it does **not** depend on the PressGang theme framework.

## Install

```bash
composer require pressgang/quartermaster
```

## Usage

```php
use PressGang\Quartermaster\Quartermaster;

$args = Quartermaster::prepare()
    ->postType('event')
    ->status('publish')
    ->paged(10)
    ->orderByMeta('start')
    ->search(get_query_var('search'))
    ->toArgs();
```

Optional Timber terminal:

```php
$posts = Quartermaster::prepare()->postType('event')->timber();
```

## Goals

- WordPress-first
- Args-first
- Timber-agnostic core with optional adapter terminals
- Explicit and debuggable (`toArgs()`, `explain()`)
