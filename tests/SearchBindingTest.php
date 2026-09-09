<?php

namespace PressGang\Quartermaster\Tests;

use PHPUnit\Framework\TestCase;
use PressGang\Quartermaster\Bindings\ArrayQueryVarSource;
use PressGang\Quartermaster\Bindings\Bind;
use PressGang\Quartermaster\Bindings\Binder;
use PressGang\Quartermaster\Quartermaster;

final class SearchBindingTest extends TestCase
{
    public function testMissingAndNullInputsUseOnlyExplicitDefaults(): void
    {
        foreach ([[], ['key' => null]] as $values) {
            $source = new ArrayQueryVarSource($values);
            self::assertSame([], Quartermaster::prepare()->bindQueryVars(['key' => Bind::search('key')], $source)->toArgs());
            self::assertSame([], Quartermaster::prepare()->bindQueryVars(['key' => Bind::relevanssi('key', true)], $source)->toArgs());
            self::assertSame(['s' => 'fallback'], Quartermaster::prepare()->bindQueryVars(['key' => Bind::search('key', default: 'fallback')], $source)->toArgs());
            self::assertSame(['s' => '', 'relevanssi' => true], Quartermaster::prepare()->bindQueryVars(['key' => Bind::relevanssi('key', allowEmpty: true, default: '')], $source)->toArgs());
        }
    }

    public function testExplicitEmptyDoesNotUseDefault(): void
    {
        $source = new ArrayQueryVarSource(['key' => '']);
        self::assertSame(['s' => 'existing'], Quartermaster::prepare()->search('existing')->bindQueryVars(['key' => Bind::search('key', 'fallback')], $source)->toArgs());
        self::assertSame(['s' => '', 'relevanssi' => true], Quartermaster::prepare()->search('existing')->bindQueryVars(['key' => Bind::relevanssi('key', true, 'fallback')], $source)->toArgs());
    }

    public function testMalformedInputsAreSkippedWithoutCastingOrApplyingDefault(): void
    {
        foreach ([[], ['nested' => ['term']], new \stdClass(), new class implements \Stringable { public function __toString(): string { throw new \RuntimeException('Must not cast objects'); } }] as $value) {
            foreach ([Bind::search('key', 'fallback'), Bind::relevanssi('key', true, '')] as $binding) {
                $query = Quartermaster::prepare()->search('existing')->bindQueryVars(['key' => $binding], new ArrayQueryVarSource(['key' => $value]));
                self::assertSame(['s' => 'existing'], $query->toArgs());
            }
        }
    }

    public function testMapAndBinderHaveIdenticalDefaultsAndScalarSemantics(): void
    {
        foreach ([null, '', '0', 0, true, false, 1.5, 'C++', 'a+b', 'two words', '%2B', ' café '] as $value) {
            $source = new ArrayQueryVarSource(['key' => $value]);
            foreach ([false, true] as $relevanssi) {
                $binding = $relevanssi ? Bind::relevanssi('key', true, '') : Bind::search('key', 'fallback');
                $map = Quartermaster::prepare()->bindQueryVars(['key' => $binding], $source)->toArgs();
                $fluent = Quartermaster::prepare()->bindQueryVars(static function (Binder $b) use ($relevanssi): void {
                    $relevanssi ? $b->relevanssi('key', true, '') : $b->search('key', 'fallback');
                }, $source)->toArgs();
                self::assertSame($map, $fluent);
            }
        }
    }

    public function testBindingsPreserveLiteralPlusAndDelegateSanitization(): void
    {
        foreach (['C++', 'a+b', '%2B', ' café ', '<b>words</b>'] as $value) {
            $source = new ArrayQueryVarSource(['key' => $value]);
            self::assertSame(Quartermaster::prepare()->search($value)->toArgs(), Quartermaster::prepare()->bindQueryVars(['key' => Bind::search('key')], $source)->toArgs());
            self::assertSame(Quartermaster::prepare()->relevanssi($value, true)->toArgs(), Quartermaster::prepare()->bindQueryVars(['key' => Bind::relevanssi('key', true)], $source)->toArgs());
        }
        self::assertSame('C++', Quartermaster::prepare()->bindQueryVars(['key' => Bind::relevanssi('key')], new ArrayQueryVarSource(['key' => 'C++']))->toArgs()['s']);
    }
}
