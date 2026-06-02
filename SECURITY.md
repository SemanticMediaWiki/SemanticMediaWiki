# Security Policy

## Supported versions

Security fixes are provided for the latest major version of Semantic MediaWiki.
Please upgrade to the current release before reporting an issue. Fixes ship in a
new release rather than as backports to older versions.

## Reporting a vulnerability

Please do **not** report security vulnerabilities through public GitHub issues,
pull requests, the mailing list, or the project wiki.

Instead, report them privately using GitHub's
[private vulnerability reporting](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/new).
Please include the affected version, steps to reproduce, and the potential
impact.

A maintainer will respond to your report, keep you informed of the progress
towards a fix, and may ask for additional information.

## Disclosure process

To minimise the risk of exploitation, please give us a reasonable opportunity to
release a fix before any public disclosure. After a report is submitted, we aim
to:

- Acknowledge the report within 15 days.
- Confirm the issue and assess its severity and impact.
- Prepare and release a fix, prioritised by severity, keeping the reporter
  informed of progress.
- Publish a security advisory once a fix is available, crediting the reporter
  unless they prefer to remain anonymous.

Remediation time depends on the severity and complexity of the issue. For
coordinated disclosure we aim to release a fix within 90 days where feasible.

Because the repository is public and can be watched by potential attackers,
please avoid describing the vulnerability in public channels, including commit
messages and issue comments, until a fix has been released.

## Vulnerabilities in MediaWiki core

Semantic MediaWiki is an extension to MediaWiki. If the issue is in MediaWiki
core or another extension rather than in Semantic MediaWiki itself, please
report it to the
[Wikimedia security team](https://www.mediawiki.org/wiki/Reporting_security_bugs)
instead.
