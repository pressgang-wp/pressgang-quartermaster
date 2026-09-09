<?php

namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Quartermaster;
use PressGang\Quartermaster\Terms\TermsBuilder;
use ReflectionClass;
use ReflectionMethod;

/**
 * Guards api-index.php against drift.
 *
 * `docs/api-index.json` is what agents read to learn this package's API — Bosun
 * points them at it as the single source of truth. It is generated from the
 * hand-maintained `api-index.php` manifest, so a method added to the builder
 * without a manifest entry is invisible to every consumer, and reads as
 * "this method does not exist".
 *
 * That is not hypothetical: toArray(), limit(), whereMetaExists(),
 * whereMetaNotExists(), whereMetaLikeAny(), whereMetaNot(), ignoreStickyPosts(),
 * applyTo(), relevanssi() and TermsBuilder::forPostType() all shipped
 * unlisted. This test fails the build instead.
 */
final class ApiIndexManifestTest extends TestCase
{
    /**
     * Every public method of a documented class must appear in the manifest.
     */
    #[DataProvider('documentedClasses')]
    public function test_manifest_lists_every_public_method(string $class): void
    {
        $listed = $this->listedMethods();

        $missing = array_values(array_diff(
            $this->publicApiOf($class),
            $listed[$class] ?? []
        ));

        $this->assertSame([], $missing, sprintf(
            "%s has public methods missing from api-index.php: %s\n"
            . 'Add them to a group, then regenerate with `composer api-index`.',
            $class,
            implode(', ', $missing)
        ));
    }

    /**
     * The manifest must not name methods that no longer exist.
     */
    #[DataProvider('documentedClasses')]
    public function test_manifest_has_no_phantom_methods(string $class): void
    {
        $listed = $this->listedMethods();

        $phantom = array_values(array_diff(
            $listed[$class] ?? [],
            $this->publicApiOf($class)
        ));

        $this->assertSame([], $phantom, sprintf(
            '%s: api-index.php names methods that do not exist: %s',
            $class,
            implode(', ', $phantom)
        ));
    }

    public function test_manifest_declares_the_entrypoint_and_principles(): void
    {
        $manifest = $this->manifest();

        $this->assertSame(Quartermaster::class, $manifest['entrypoint'] ?? null);
        $this->assertNotEmpty($manifest['principles'] ?? []);
    }

    /**
     * @return array<int, array{0: class-string}>
     */
    public static function documentedClasses(): array
    {
        return [
            Quartermaster::class => [Quartermaster::class],
            TermsBuilder::class => [TermsBuilder::class],
            Bind::class => [Bind::class],
        ];
    }

    /**
     * Public methods that form the documented API surface: no constructors and
     * no magic methods (`__call` is the macro dispatcher, not an API entry).
     *
     * @param class-string $class
     * @return list<string>
     */
    private function publicApiOf(string $class): array
    {
        $names = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if ($method->isConstructor() || $method->isDestructor() || str_starts_with($name, '__')) {
                continue;
            }

            $names[$name] = true;
        }

        $names = array_keys($names);
        sort($names);

        return $names;
    }

    /**
     * Method names in the manifest, keyed by class.
     *
     * @return array<class-string, list<string>>
     */
    private function listedMethods(): array
    {
        $listed = [];

        foreach ($this->manifest()['groups'] ?? [] as [$class, $methods]) {
            foreach ($methods as $method) {
                $listed[$class][$method] = true;
            }
        }

        return array_map(
            static function (array $methods): array {
                $names = array_keys($methods);
                sort($names);

                return $names;
            },
            $listed
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        /** @var array<string, mixed> $manifest */
        $manifest = require \dirname(__DIR__) . '/api-index.php';

        return $manifest;
    }
}
