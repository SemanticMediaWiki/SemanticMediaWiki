# SMW configuration defaults

Authoritative defaults for every `$smwg*` setting live in
[`extension.json`](../extension.json)'s `config` block. Each entry there
carries a one-line `description` used by `Special:Version`.

This file is the long-form companion: a place to put rationale, examples,
or cross-cutting context that doesn't fit on one line. It is **per-setting
opt-in** — most settings need only the manifest description and never
appear here. Settings that do appear get an `## $smwg<Name>` section with a
description, `**Since:** <version>` line, default value or pointer, and any
relevant upstream / related links.

## Subsystem-specific docs

For settings owned by a specific subsystem, narrative lives next to the
subsystem code rather than here. Cross-link out:

- **Elasticsearch / ElasticStore** —
  [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md)
- **Importer / vocabularies** —
  [`src/Importer/README.md`](../src/Importer/README.md)
