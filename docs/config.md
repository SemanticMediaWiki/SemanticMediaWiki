# SMW configuration defaults

Authoritative defaults for every `$smwg*` setting live in
[`extension.json`](../extension.json)'s `config` block. This file is the
human-readable reference: each setting from the manifest gets a section
here covering its purpose, default value, and override semantics.

## Format

```
## $smwg<Name>

One-paragraph description of what the setting does.

**Since:** <version>
**Default:** `<value or pointer>`
**Related:** [optional links to subsystem docs or upstream references]
```

## Subsystem-specific docs

Settings owned by a specific subsystem also have dedicated documentation
beyond the per-setting boxes here:

- **Elasticsearch / ElasticStore** —
  [`src/Elastic/docs/config.md`](../src/Elastic/docs/config.md)
- **Importer / vocabularies** —
  [`src/Importer/README.md`](../src/Importer/README.md)
