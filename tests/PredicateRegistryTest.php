<?php

declare(strict_types=1);

namespace K2gl\InToto\Tests;

use K2gl\InToto\Predicate;
use K2gl\InToto\PredicateRegistry;
use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(PredicateRegistry::class)]
#[CoversClass(Statement::class)]
final class PredicateRegistryTest extends TestCase
{
    private const TYPE = 'https://example.com/predicate/v1';

    public function testResolvesRegisteredPredicate(): void
    {
        $registry = new PredicateRegistry;
        $registry->register(self::TYPE, static fn (array $p): Predicate => self::fakePredicate($p));

        fact($registry->has(self::TYPE))->true();

        $resolved = $registry->resolve(self::TYPE, ['k' => 'v']);
        fact($resolved instanceof Predicate)->true();
        fact($resolved?->toArray())->is(['k' => 'v']);
    }

    public function testResolveUnknownTypeReturnsNull(): void
    {
        $registry = new PredicateRegistry;

        fact($registry->has(self::TYPE))->false();
        fact($registry->resolve(self::TYPE, ['k' => 'v']))->null();
    }

    public function testStatementResolvesPredicateViaRegistry(): void
    {
        $registry = new PredicateRegistry;
        $registry->register(self::TYPE, static fn (array $p): Predicate => self::fakePredicate($p));
        $statement = new Statement([new ResourceDescriptor(uri: 'pkg:demo')], self::TYPE, ['k' => 'v']);

        $predicate = $statement->predicate($registry);
        fact($predicate instanceof Predicate)->true();
        // The raw property remains available regardless.
        fact($statement->predicate)->is(['k' => 'v']);
    }

    public function testStatementFallsBackToRawArrayWhenUnregistered(): void
    {
        $statement = new Statement([new ResourceDescriptor(uri: 'pkg:demo')], self::TYPE, ['k' => 'v']);

        fact($statement->predicate(new PredicateRegistry))->is(['k' => 'v']);
    }

    public function testDefaultRegistryIsShared(): void
    {
        fact(PredicateRegistry::default())->is(PredicateRegistry::default());
    }

    /** @param array<string, mixed> $data */
    private static function fakePredicate(array $data): Predicate
    {
        return new class ($data) implements Predicate {
            /** @param array<string, mixed> $data */
            public function __construct(private readonly array $data) {}

            public function predicateType(): string
            {
                return 'https://example.com/predicate/v1';
            }

            public function toArray(): array
            {
                return $this->data;
            }
        };
    }
}
