<?php

declare(strict_types=1);

namespace K2gl\InToto\Tests;

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
final class StatementTest extends TestCase
{
    public function testRoundTripsThroughJson(): void
    {
        $statement = new Statement(
            [new ResourceDescriptor(name: 'pkg:composer/k2gl/dsse@1.0.0', digest: ['sha256' => 'abc123'])],
            'https://slsa.dev/provenance/v1',
            ['buildType' => 'https://example.com/build'],
        );

        $json = $statement->toJson();
        $parsed = Statement::fromJson($json);

        fact($parsed->predicateType)->is('https://slsa.dev/provenance/v1');
        fact($parsed->predicate)->is(['buildType' => 'https://example.com/build']);
        fact($parsed->subject[0]->name)->is('pkg:composer/k2gl/dsse@1.0.0');
        fact($parsed->subject[0]->digest)->is(['sha256' => 'abc123']);
        fact($parsed->toJson())->is($json);
    }

    public function testExposesTheCanonicalTypeInJson(): void
    {
        $json = (new Statement([new ResourceDescriptor(digest: ['sha256' => 'x'])], 'https://example.com/p'))->toJson();

        fact(str_contains($json, '"_type":"https://in-toto.io/Statement/v1"'))->true();
        fact(Statement::PAYLOAD_TYPE)->is('application/vnd.in-toto+json');
    }

    public function testParsesV01Statement(): void
    {
        $json = <<<'JSON'
            {
              "_type": "https://in-toto.io/Statement/v0.1",
              "predicateType": "https://slsa.dev/provenance/v0.2",
              "subject": [
                {
                  "name": "pkg:composer/k2gl/dsse@1.0.0",
                  "digest": {"sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"}
                }
              ],
              "predicate": {"builder": {"id": "https://github.com/actions/runner"}}
            }
            JSON;

        $statement = Statement::fromJson($json);

        fact($statement->version)->is(StatementVersion::V0_1);
        fact($statement->predicateType)->is('https://slsa.dev/provenance/v0.2');
        fact($statement->subject[0]->name)->is('pkg:composer/k2gl/dsse@1.0.0');
        fact($statement->subject[0]->digest)->is(['sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855']);
        fact($statement->predicate)->is(['builder' => ['id' => 'https://github.com/actions/runner']]);
    }

    public function testRoundTripsV01(): void
    {
        $statement = new Statement(
            subject: [new ResourceDescriptor(name: 'app', digest: ['sha256' => 'abc123'])],
            predicateType: 'https://slsa.dev/provenance/v0.2',
            predicate: ['builder' => ['id' => 'https://ci.example.com']],
            version: StatementVersion::V0_1,
        );

        $json = $statement->toJson();

        fact(str_contains($json, '"_type":"https://in-toto.io/Statement/v0.1"'))->true();

        $parsed = Statement::fromJson($json);

        fact($parsed->version)->is(StatementVersion::V0_1);
        fact($parsed->toJson())->is($json);
    }

    public function testDefaultsToV1(): void
    {
        $statement = new Statement(
            [new ResourceDescriptor(digest: ['sha256' => 'x'])],
            'https://example.com/p',
        );

        fact($statement->version)->is(StatementVersion::V1);
        fact(Statement::TYPE)->is('https://in-toto.io/Statement/v1');
    }

    public function testRejectsUnknownType(): void
    {
        $this->expectException(InvalidStatementException::class);
        Statement::fromJson('{"_type":"https://in-toto.io/Statement/v2","subject":[{"digest":{"sha256":"x"}}],"predicateType":"p"}');
    }

    public function testRejectsEmptySubject(): void
    {
        $this->expectException(InvalidStatementException::class);
        new Statement([], 'https://example.com/p');
    }

    public function testRejectsMissingPredicateType(): void
    {
        $this->expectException(InvalidStatementException::class);
        Statement::fromJson('{"_type":"https://in-toto.io/Statement/v1","subject":[{"digest":{"sha256":"x"}}]}');
    }
}
