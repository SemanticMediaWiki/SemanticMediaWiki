---
name: Make a release
about: Keep track of the tasks for making a release
title: Make the x.y.z realease
labels: roadmap
assignees: ''

---

## Tasks

- [ ] Update release notes
  - [ ] Add missing commits tagged with [releasenotes](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pulls?q=is%3Apr+is%3Aopen+label%3Areleasenotes)
  - [ ] Add RELEASE-NOTES to /docs/releasenotes/
  - [ ] Update /docs/releasenotes/README
- [ ] Update INSTALL - not just version number
- [ ] Update composer.json
- [ ] Update version number
  - [ ] extension.json
  - [ ] COMPATIBILITY
- [ ] Create tag
- [ ] Update `semantic-mediawiki.org`
  - [ ] Run `composer update`
  - [ ] Touch `LocalSettings.php`
  - [ ] Update the SMW configuration
  - [ ] Check [Special:Version](https://www.semantic-mediawiki.org/wiki/Special:Version)
- [ ] Update [roadmap](https://www.semantic-mediawiki.org/wiki/Roadmap) if applicable
- [ ] Update version on wikidata, en, mw, and Free Software Directory
- [ ] Announce on wiki 
- [ ] <s>Announce via mail (use series instead of branch as wording)</s>
- [ ] <s>Do a release tweet on Twitter (Also mention if it is a feature, maintenance or security release)</s>
- [ ] Close milestone
- [ ] Update project
