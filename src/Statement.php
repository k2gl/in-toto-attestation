<?php

declare(strict_types=1);

namespace K2gl\InToto;

use K2gl\Dsse\Envelope;
use K2gl\Dsse\Signer;
use K2gl\InToto\Exception\InvalidStatementException;
use JsonException;

/**
 * An in-toto Statement: the payload of an in-toto attestation. It binds a set of
 * subjects (the artifacts the attestation is about) to a predicate of a given
 * type (the claim being made). Statements travel inside a DSSE envelope whose
 * payload type is "application/vnd.in-toto+json" or, since attestation spec
 * v1.2, the predicate-specific "application/vnd.in-toto.<predicate>+json".
 *
 * Both schema versions are supported (see StatementVersion): v1, the default for
 * new statements, and the legacy v0.1 still carried by many real-world bundles.
 *
 * @see https://github.com/in-toto/attestation/blob/main/spec/v1/statement.md
 */
final class Statement
{
    public const TYPE = 'https://in-toto.io/Statement/v1';
    public const PAYLOAD_TYPE = 'application/vnd.in-toto+json';

    /** The generic payload type, or a predicate-specific one such as "application/vnd.in-toto.provenance+json". */
    private const PAYLOAD_TYPE_PATTERN = '#^application/vnd\.in-toto(\.[a-z0-9]+(-[a-z0-9]+)*)?\+json$#i';

    /**
     * @param list<ResourceDescriptor> $subject   at least one subject
     * @param array<string, mixed>     $predicate the predicate object (may be empty)
     */
    public function __construct(
        public readonly array $subject,
        public readonly string $predicateType,
        public readonly array $predicate = [],
        public readonly StatementVersion $version = StatementVersion::V1,
    ) {
        if ($subject === []) {
            throw new InvalidStatementException('A Statement must have at least one subject.');
        }

        if ($predicateType === '') {
            throw new InvalidStatementException('A Statement must have a non-empty "predicateType".');
        }
    }

    /** Sign this statement into a DSSE in-toto envelope. */
    public function sign(Signer ...$signers): Envelope
    {
        return Envelope::sign($this->toJson(), self::PAYLOAD_TYPE, ...$signers);
    }

    /**
     * Resolve the predicate to a typed {@see Predicate} via the registry, or
     * fall back to the raw predicate array when no factory is registered for
     * this statement's predicateType. The raw {@see $predicate} property is
     * always available regardless.
     *
     * @return Predicate|array<string, mixed>
     */
    public function predicate(?PredicateRegistry $registry = null): Predicate|array
    {
        return ($registry ?? PredicateRegistry::default())
            ->resolve($this->predicateType, $this->predicate) ?? $this->predicate;
    }

    /**
     * Whether $payloadType is an in-toto DSSE payload type: either the generic
     * "application/vnd.in-toto+json" or the predicate-specific
     * "application/vnd.in-toto.<predicate>+json" form the attestation spec
     * added in v1.2, where <predicate> is the predicate's spec filename.
     *
     * The media type is a hint only. What a predicate actually is comes from
     * the authenticated "predicateType" inside the Statement, so never branch
     * on this value alone.
     *
     * @see https://github.com/in-toto/attestation/blob/main/spec/v1/envelope.md
     */
    public static function isPayloadType(string $payloadType): bool
    {
        return preg_match(self::PAYLOAD_TYPE_PATTERN, $payloadType) === 1;
    }

    /**
     * Parse the payload of a DSSE in-toto envelope into a Statement. The
     * envelope's signatures must be verified separately (e.g. via
     * Envelope::verify()); this only checks the payload type and decodes.
     */
    public static function fromEnvelope(Envelope $envelope): self
    {
        if (! self::isPayloadType($envelope->payloadType)) {
            throw new InvalidStatementException(sprintf(
                'Envelope payloadType is "%s", expected "%s" or "application/vnd.in-toto.<predicate>+json".',
                $envelope->payloadType,
                self::PAYLOAD_TYPE,
            ));
        }

        return self::fromJson($envelope->payload);
    }

    /**
     * @return array{
     *     _type: string,
     *     subject: list<array<string, mixed>>,
     *     predicateType: string,
     *     predicate?: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        $subjects = [];

        foreach ($this->subject as $descriptor) {
            $subjects[] = $descriptor->toArray();
        }

        $result = [
            '_type' => $this->version->value,
            'subject' => $subjects,
            'predicateType' => $this->predicateType,
        ];

        if ($this->predicate !== []) {
            $result['predicate'] = $this->predicate;
        }

        return $result;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        try {
            /** @var mixed $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidStatementException('Statement is not valid JSON: ' . $e->getMessage(), previous: $e);
        }

        if (! is_array($data)) {
            throw new InvalidStatementException('Statement must be a JSON object.');
        }

        return self::fromArray($data);
    }

    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        $type = $data['_type'] ?? null;
        $version = is_string($type) ? StatementVersion::tryFrom($type) : null;

        if ($version === null) {
            throw new InvalidStatementException(sprintf(
                'Statement "_type" must be "%s" or "%s".',
                StatementVersion::V1->value,
                StatementVersion::V0_1->value,
            ));
        }

        $rawSubjects = $data['subject'] ?? null;

        if (! is_array($rawSubjects) || $rawSubjects === []) {
            throw new InvalidStatementException('Statement must contain a non-empty "subject" array.');
        }
        $subjects = [];

        foreach ($rawSubjects as $raw) {
            if (! is_array($raw)) {
                throw new InvalidStatementException('Each subject must be a JSON object.');
            }
            $subjects[] = ResourceDescriptor::fromArray($raw);
        }

        $predicateType = $data['predicateType'] ?? null;

        if (! is_string($predicateType) || $predicateType === '') {
            throw new InvalidStatementException('Statement must contain a non-empty "predicateType".');
        }

        return new self(
            subject: $subjects,
            predicateType: $predicateType,
            predicate: self::predicateObject($data['predicate'] ?? []),
            version: $version,
        );
    }

    /** @return array<string, mixed> */
    private static function predicateObject(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidStatementException('"predicate" must be a JSON object.');
        }

        if ($value !== [] && array_is_list($value)) {
            throw new InvalidStatementException('"predicate" must be a JSON object, not an array.');
        }
        $predicate = [];

        foreach ($value as $key => $item) {
            $predicate[(string) $key] = $item;
        }

        return $predicate;
    }
}
