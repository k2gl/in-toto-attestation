# Changelog

## 1.1.0

- **Statement v0.1** support alongside v1. `fromJson()` and `fromEnvelope()` now parse
  both `https://in-toto.io/Statement/v1` and the legacy `…/v0.1` payloads carried by many
  real-world Sigstore bundles, and `toJson()` round-trips the version it was given.
- New **`StatementVersion`** enum, exposed as `Statement::$version`. The constructor gains
  an optional `version` argument that defaults to v1, so existing code is unaffected.

## 1.0.0

First public release. A faithful, typed implementation of the in-toto Attestation
Framework's **Statement v1**, built on [`k2gl/dsse`](https://github.com/k2gl/dsse):

- **`Statement`** — immutable value object (`_type`, `subject`, `predicateType`,
  `predicate`) with lossless `fromJson()` / `toJson()`, plus `sign()` (wrap into a
  DSSE `application/vnd.in-toto+json` envelope) and `fromEnvelope()` (parse a verified
  envelope back into a Statement).
- **`ResourceDescriptor`** — subject/resource identity by name, URI, digest set,
  inline content, media type, download location and annotations; requires at least one
  of uri/digest/content.
- Every error implements `InTotoException`.
