<?php

declare(strict_types=1);

namespace K2gl\InToto\Tests;

use K2gl\InToto\Exception\InvalidStatementException;
use K2gl\InToto\ResourceDescriptor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(ResourceDescriptor::class)]
#[CoversClass(InvalidStatementException::class)]
final class ResourceDescriptorTest extends TestCase
{
    public function testSerializesOnlySetFields(): void
    {
        $descriptor = new ResourceDescriptor(name: 'artifact', digest: ['sha256' => 'deadbeef']);

        fact($descriptor->toArray())->is(['name' => 'artifact', 'digest' => ['sha256' => 'deadbeef']]);
    }

    public function testRoundTripsContentAsBase64(): void
    {
        $descriptor = new ResourceDescriptor(name: 'inline', content: 'raw bytes');
        $array = $descriptor->toArray();

        fact($array['content'])->is(base64_encode('raw bytes'));
        fact(ResourceDescriptor::fromArray($array)->content)->is('raw bytes');
    }

    public function testRequiresAtLeastOneIdentifier(): void
    {
        // act + assert
        fact(static fn () => new ResourceDescriptor(name: 'name only'))->throws(InvalidStatementException::class);
    }

    public function testRejectsNonStringDigestValue(): void
    {
        // act + assert
        fact(static fn () => ResourceDescriptor::fromArray(['digest' => ['sha256' => 123]]))
            ->throws(InvalidStatementException::class);
    }
}
