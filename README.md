# k2gl/in-toto-attestation

A faithful, typed PHP implementation of the
[in-toto Attestation Framework](https://github.com/in-toto/attestation) **Statement** — both
the current **v1** and the legacy **v0.1** still carried by many real-world bundles —
built on [`k2gl/dsse`](https://github.com/k2gl/dsse).

An in-toto attestation is a signed claim ("predicate") about one or more artifacts
("subjects"). The claim is a **Statement**, carried inside a DSSE envelope with payload
type `application/vnd.in-toto+json`. This package gives you typed, validated `Statement`
and `ResourceDescriptor` value objects plus the sign/parse glue to DSSE.

## Install

```bash
composer require k2gl/in-toto-attestation
```

Requires PHP 8.1+. Pulls in `k2gl/dsse`. The example signers use `ext-sodium`
(Ed25519) / `ext-openssl` (ECDSA), both bundled with PHP.

## Usage

### Build and sign a statement

```php
use K2gl\InToto\Statement;
use K2gl\InToto\ResourceDescriptor;
use K2gl\Dsse\Ed25519Signer;

$statement = new Statement(
    subject: [
        new ResourceDescriptor(
            name: 'pkg:composer/k2gl/dsse@1.0.0',
            digest: ['sha256' => '…'],
        ),
    ],
    predicateType: 'https://slsa.dev/provenance/v1',
    predicate: ['buildDefinition' => [/* … */], 'runDetails' => [/* … */]],
);

$envelope = $statement->sign($signer);   // a K2gl\Dsse\Envelope
echo $envelope->toJson();
```

### Verify and parse

```php
use K2gl\InToto\Statement;
use K2gl\Dsse\Envelope;
use K2gl\Dsse\Ed25519Verifier;

$envelope = Envelope::fromJson($json);

$envelope->verify($verifier);              // DSSE signature check (throws on failure)
$statement = Statement::fromEnvelope($envelope);

$statement->predicateType;                 // 'https://slsa.dev/provenance/v1'
$statement->subject[0]->digest;            // ['sha256' => '…']
```

`fromEnvelope()` checks the envelope's `payloadType` and decodes the payload — always
verify the envelope's signatures (via `k2gl/dsse`) before trusting the result.

### Statement versions

Real-world Sigstore bundles carry in-toto Statements in two schema versions: the current
`v1` and the legacy `v0.1` (often wrapping a SLSA Provenance `v0.2` predicate). `fromJson()`
and `fromEnvelope()` parse both and expose which one was decoded:

```php
use K2gl\InToto\StatementVersion;

$statement = Statement::fromEnvelope($envelope);
$statement->version === StatementVersion::V0_1;   // true for a legacy bundle
```

New statements default to `v1`. To build a `v0.1` statement, pass the version explicitly:

```php
$statement = new Statement(
    subject: [new ResourceDescriptor(name: 'app', digest: ['sha256' => '…'])],
    predicateType: 'https://slsa.dev/provenance/v0.2',
    predicate: [/* … */],
    version: StatementVersion::V0_1,
);
```

## Scope

This package models the **Statement** layer (the generic envelope payload). Concrete
predicate types — SLSA Provenance, SPDX/CycloneDX, etc. — are intentionally out of scope
and can be carried as a typed array in `predicate`, or modelled by companion packages.

## License

MIT — see [LICENSE](LICENSE). Independent, clean-room implementation of the in-toto
Attestation specification (Apache-2.0).
