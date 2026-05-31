<?php

declare(strict_types=1);

namespace K2gl\InToto;

/**
 * The schema version of an in-toto Statement, identified by its `_type`. Both
 * appear in real-world Sigstore bundles: v1 is current, while v0.1 is the legacy
 * version still widely emitted (often carrying a SLSA Provenance v0.2 predicate).
 * The two differ only in the `_type` URI, so this package models both with the
 * same Statement and ResourceDescriptor value objects.
 *
 * @see https://github.com/in-toto/attestation/blob/main/spec/v1/statement.md
 */
enum StatementVersion: string
{
    case V0_1 = 'https://in-toto.io/Statement/v0.1';
    case V1 = 'https://in-toto.io/Statement/v1';
}
