<?php

declare(strict_types=1);

namespace K2gl\InToto\Tests;

use K2gl\Dsse\Ed25519Signer;
use K2gl\Dsse\Ed25519Verifier;
use K2gl\Dsse\Envelope;
use K2gl\InToto\Exception\InvalidStatementException;
use K2gl\InToto\ResourceDescriptor;
use K2gl\InToto\Statement;
use K2gl\InToto\StatementVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(Statement::class)]
#[CoversClass(ResourceDescriptor::class)]
#[CoversClass(StatementVersion::class)]
#[CoversClass(InvalidStatementException::class)]
final class EnvelopeIntegrationTest extends TestCase
{
    public function testSignVerifyAndParseRoundTrip(): void
    {
        $keypair = sodium_crypto_sign_keypair();
        $signer = new Ed25519Signer(sodium_crypto_sign_secretkey($keypair), 'ed-1');
        $verifier = new Ed25519Verifier(sodium_crypto_sign_publickey($keypair));

        $statement = new Statement(
            [new ResourceDescriptor(name: 'app', digest: ['sha256' => 'abc'])],
            'https://slsa.dev/provenance/v1',
            ['builder' => ['id' => 'https://ci.example.com']],
        );

        $envelope = $statement->sign($signer);

        fact($envelope->payloadType)->is(Statement::PAYLOAD_TYPE);
        fact($envelope->verify($verifier))->is($statement->toJson());

        $parsed = Statement::fromEnvelope($envelope);
        fact($parsed->predicateType)->is('https://slsa.dev/provenance/v1');
        fact($parsed->subject[0]->digest)->is(['sha256' => 'abc']);
        fact($parsed->predicate)->is(['builder' => ['id' => 'https://ci.example.com']]);
    }

    public function testSignVerifyAndParseV01RoundTrip(): void
    {
        $keypair = sodium_crypto_sign_keypair();
        $signer = new Ed25519Signer(sodium_crypto_sign_secretkey($keypair), 'ed-1');
        $verifier = new Ed25519Verifier(sodium_crypto_sign_publickey($keypair));

        $statement = new Statement(
            subject: [new ResourceDescriptor(name: 'app', digest: ['sha256' => 'abc'])],
            predicateType: 'https://slsa.dev/provenance/v0.2',
            predicate: ['builder' => ['id' => 'https://ci.example.com']],
            version: StatementVersion::V0_1,
        );

        $envelope = $statement->sign($signer);

        fact($envelope->verify($verifier))->is($statement->toJson());

        $parsed = Statement::fromEnvelope($envelope);

        fact($parsed->version)->is(StatementVersion::V0_1);
        fact($parsed->predicateType)->is('https://slsa.dev/provenance/v0.2');
        fact($parsed->subject[0]->digest)->is(['sha256' => 'abc']);
    }

    public function testFromEnvelopeRejectsWrongPayloadType(): void
    {
        $envelope = new Envelope('{}', 'application/json', []);

        $this->expectException(InvalidStatementException::class);
        Statement::fromEnvelope($envelope);
    }
}
