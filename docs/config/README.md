# SMW configuration defaults

Authoritative defaults for every `$smwg*` setting live in `extension.json`'s
`config` block. This directory holds per-setting narrative documentation that
is too long for `extension.json`'s `description` field.

## Convention

- One file per setting: `docs/config/<settingName>.md` (no `smwg` prefix in
  the filename — e.g. `EnabledSpecialPage.md` for `$smwgEnabledSpecialPage`).
- Each file includes:
  - `# $smwg<Name>` heading.
  - One-paragraph description.
  - `**Since:** <version>` line.
  - `**Default:** <value or pointer>` line.
  - Any links to upstream / related docs.
- The `extension.json` `config.<Name>.description` field carries a one-line
  summary used by `Special:Version`. Cross-link to this directory from there
  when narrative is needed.

## Why

Migration history and design rationale: see
`docs/plans/2026-05-03-extension-json-config-migration-design.md`.

The previous home of these docs (`src/DefaultSettings.php` inline comments)
was removed in 7.0.0 along with the bespoke `setupGlobals()` pipeline; see
the 7.0.0 release notes.
