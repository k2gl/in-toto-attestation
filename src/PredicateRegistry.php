<?php

declare(strict_types=1);

namespace K2gl\InToto;

/**
 * Maps a predicateType URI to a factory that turns a raw predicate array into a
 * typed {@see Predicate}. Predicate packages (e.g. k2gl/slsa-provenance) register
 * their types here; {@see Statement::predicate()} consults the registry. The
 * static {@see default()} instance is the conventional shared registry.
 */
final class PredicateRegistry
{
    /** @var array<string, callable(array<string, mixed>): Predicate> */
    private array $factories = [];

    private static ?self $default = null;

    /**
     * @param callable(array<string, mixed>): Predicate $factory
     */
    public function register(string $predicateType, callable $factory): void
    {
        $this->factories[$predicateType] = $factory;
    }

    public function has(string $predicateType): bool
    {
        return isset($this->factories[$predicateType]);
    }

    /**
     * @param array<string, mixed> $predicate
     *
     * @return Predicate|null null when no factory is registered for the type
     */
    public function resolve(string $predicateType, array $predicate): ?Predicate
    {
        $factory = $this->factories[$predicateType] ?? null;

        return $factory === null ? null : $factory($predicate);
    }

    /** The shared, process-wide registry that predicate packages register into. */
    public static function default(): self
    {
        return self::$default ??= new self;
    }
}
