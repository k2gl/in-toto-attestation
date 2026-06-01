<?php

declare(strict_types=1);

namespace K2gl\InToto;

use K2gl\InToto\Exception\InvalidStatementException;

/**
 * An in-toto ResourceDescriptor: identifies an artifact by name, URI,
 * cryptographic digest and/or inline content, with optional media type,
 * download location and annotations. At least one of uri, digest or content
 * must be set so the resource is identifiable.
 *
 * @see https://github.com/in-toto/attestation/blob/main/spec/v1/resource_descriptor.md
 */
final class ResourceDescriptor
{
    /**
     * @param array<string, string>     $digest      algorithm => hex (or algorithm-specific) value
     * @param array<string, mixed>|null $annotations
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $uri = null,
        public readonly array $digest = [],
        public readonly ?string $mediaType = null,
        public readonly ?string $downloadLocation = null,
        public readonly ?array $annotations = null,
        public readonly ?string $content = null,
    ) {
        if ($uri === null && $digest === [] && $content === null) {
            throw new InvalidStatementException(
                'A resource descriptor must set at least one of "uri", "digest" or "content".'
            );
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];

        if ($this->name !== null) {
            $out['name'] = $this->name;
        }

        if ($this->uri !== null) {
            $out['uri'] = $this->uri;
        }

        if ($this->digest !== []) {
            $out['digest'] = $this->digest;
        }

        if ($this->content !== null) {
            $out['content'] = base64_encode($this->content);
        }

        if ($this->downloadLocation !== null) {
            $out['downloadLocation'] = $this->downloadLocation;
        }

        if ($this->mediaType !== null) {
            $out['mediaType'] = $this->mediaType;
        }

        if ($this->annotations !== null) {
            $out['annotations'] = $this->annotations;
        }

        return $out;
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: self::optionalString($data, 'name'),
            uri: self::optionalString($data, 'uri'),
            digest: self::digestSet($data['digest'] ?? null),
            mediaType: self::optionalString($data, 'mediaType'),
            downloadLocation: self::optionalString($data, 'downloadLocation'),
            annotations: self::optionalObject($data['annotations'] ?? null, 'annotations'),
            content: self::optionalContent($data['content'] ?? null),
        );
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidStatementException(sprintf('Resource field "%s" must be a string.', $key));
        }

        return $value;
    }

    /** @return array<string, string> */
    private static function digestSet(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidStatementException('"digest" must be an object of algorithm => hex string.');
        }
        $digest = [];

        foreach ($value as $algorithm => $hex) {
            if (! is_string($hex)) {
                throw new InvalidStatementException('Each "digest" value must be a string.');
            }
            $digest[(string) $algorithm] = $hex;
        }

        return $digest;
    }

    /** @return array<string, mixed>|null */
    private static function optionalObject(mixed $value, string $field): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            throw new InvalidStatementException(sprintf('"%s" must be an object.', $field));
        }
        $object = [];

        foreach ($value as $key => $item) {
            $object[(string) $key] = $item;
        }

        return $object;
    }

    private static function optionalContent(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidStatementException('"content" must be a base64 string.');
        }
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            throw new InvalidStatementException('"content" is not valid base64.');
        }

        return $decoded;
    }
}
