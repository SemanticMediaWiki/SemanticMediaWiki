# Release process

This document is the step-by-step reference for cutting a Semantic MediaWiki release.
For *which* changes belong in a patch, minor, or major release, see [RELEASE-POLICY.md](RELEASE-POLICY.md).

## Overview

* Releases are cut from `master`.
* The version lives in a single place, `extension.json`. During development it carries an `-alpha` suffix (for example `7.1.1-alpha`).
* The release itself is one PR, followed by a tag, a GitHub release, the semantic-mediawiki.org pages, and a bump of `master` to the next development version.

## 1. Prepare the release commit

Branch off `master` and make these changes in a single commit named `X.Y.Z release`:

* **`extension.json`**: drop the `-alpha` suffix, for example `7.1.0-alpha` to `7.1.0`.
* **`docs/releasenotes/RELEASE-NOTES-X.Y.Z.md`**: set the header date (`Released on TBD.` to `Released on Month D, YYYY.`) and confirm every user-facing change since the previous release has a bullet. The notes file and its index entry in `docs/releasenotes/README.md` already exist from the development cycle. To check completeness, review `git log <previous-release-tag>..master` (for example `git log 7.0.0..master`); internal, CI, test, and localisation commits are intentionally left out.
* **`docs/COMPATIBILITY.md`**: in the platform status table, add a new `X.Y.x` **Stable release** row (First release and Latest release both set to the release date in ISO `YYYY-MM-DD` form; PHP and MediaWiki ranges) and change the previous stable line to Obsolete release. Add a matching `X.Y.x` row to the ElasticStore support table. Exactly one row is ever "Stable release". Leave the PHP-version cells of old rows alone; some read like a version number (for example `7.1.0`) but are PHP versions, not SMW versions.

Only touch `docs/INSTALL.md` or `composer.json` when the minimum requirements or install instructions actually change (typically major releases).

Open the PR as a draft, wait for CI to pass, then mark it ready.

> Merge the PR with **"Squash and merge"** (it lands on `master` as `X.Y.Z release (#NNNN)`). This is required for the release commit to be signed.

## 2. Tag and publish the GitHub release

After the PR merges, create the tag and GitHub release on the squash-merge commit (the new `master` HEAD). The release title is `SMW X.Y.Z`; the tag is the bare version `X.Y.Z` (no `v` prefix):

```shell
gh release create X.Y.Z \
  --target <merge-commit-sha> \
  --title "SMW X.Y.Z" \
  --notes "$(cat <<'EOF'
Released on Month D, YYYY - [RELEASE NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/releasenotes/RELEASE-NOTES-X.Y.Z.md)

**Note:** The provided source code links do not include required dependencies. The recommended way to install Semantic MediaWiki is documented in the [installation guide.](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/INSTALL.md)
EOF
)"
```

Passing `--target` creates the `X.Y.Z` tag at that commit; no separate `git tag` or push is needed.

**[Packagist](https://packagist.org/packages/mediawiki/semantic-media-wiki)** updates automatically through a GitHub webhook within about a minute. Confirm the new version appears there before announcing.

## 3. Publish the semantic-mediawiki.org pages

Mirror the previous release's two pages, but **without** `<!--T:N-->` translation markers; an admin marks them for translation afterwards through `Special:PageTranslation`.

* **`Semantic MediaWiki X.Y.Z`**: the `{{SMW release}}` version page. Set `date=` (ISO), `release_prev=SMW <previous>`, the compatibility parameters, a short `<translate>` intro based on the release notes summary, and `{{#github:docs/releasenotes/RELEASE-NOTES-X.Y.Z.md}}`, which transcludes the notes live from `master`. Then edit the **previous** version page's `release_next=` to point at this release.
* **`Semantic MediaWiki X.Y.Z released`**: the `{{News item}}` announcement.
* Update the `{{SMW release main page}}` template to the new version.
* Create a redirect from **SMW X.Y.Z** to **Semantic MediaWiki X.Y.Z**

Then update the version listed on [Wikidata](https://www.wikidata.org/wiki/Q20728) and [MediaWiki.org](https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki).

## 4. Start the next development cycle

Bump `master` to the next version with an `-alpha` suffix, in one commit:

* **`extension.json`**: set the next `-alpha` version. **Default to the next patch** (for example `7.1.0` becomes `7.1.1-alpha`). Only move to the next minor (`7.2.0-alpha`) once a feature change actually lands and warrants it.
* **`docs/releasenotes/RELEASE-NOTES-<next>.md`**: add a scaffold (a patch scaffold states "only bug fixes"; use a minor scaffold when features are expected).
* **`docs/releasenotes/README.md`**: link the new notes (bold for minor and major, plain text for patch).
