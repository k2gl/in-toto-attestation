<?php

declare(strict_types=1);

namespace K2gl\InToto\Tests;

use K2gl\InToto\ResourceDescriptor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(ResourceDescriptor::class)]
final class ResourceDescriptorDigestTest extends TestCase
{
    public function testDigestForReturnsValueOrNull(): void
    {
        $descriptor = new ResourceDescriptor(digest: ['sha256' => 'abc123']);

        fact($descriptor->digestFor('sha256'))->is('abc123');
        fact($descriptor->digestFor('sha512'))->null();
    }

    public function testHasDigestMatchesCaseInsensitively(): void
    {
        $descriptor = new ResourceDescriptor(digest: ['sha256' => 'ABC123']);

        fact($descriptor->hasDigest('sha256', 'abc123'))->true();
        fact($descriptor->hasDigest('sha256', 'deadbeef'))->false();
        fact($descriptor->hasDigest('sha512', 'abc123'))->false();
    }
}
