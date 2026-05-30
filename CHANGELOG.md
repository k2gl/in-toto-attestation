# Changelog

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
