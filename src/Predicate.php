<?php

declare(strict_types=1);

namespace K2gl\InToto;

/**
 * A typed in-toto predicate. Implementations model a specific predicate type
 * (SLSA Provenance, VSA, SPDX, …) and serialize to the predicate JSON object.
 * Register a factory in a {@see PredicateRegistry} so a {@see Statement} can
 * resolve its raw predicate array into the typed object.
 */
interface Predicate
{
    /** The predicateType URI this predicate is carried under. */
    public function predicateType(): string;

    /** @return array<string, mixed> the predicate JSON object */
    public function toArray(): array;
}
