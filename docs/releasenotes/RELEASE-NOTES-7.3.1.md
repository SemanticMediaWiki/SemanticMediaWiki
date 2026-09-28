# Semantic MediaWiki 7.3.1

Released on TBD.

This is a [patch release](../RELEASE-POLICY.md). Thus, it contains only bug fixes and security fixes, and no new features. Some of the security fixes tighten input handling and may reject requests that previously succeeded.

Like SMW 7.3.0, this version is compatible with MediaWiki 1.43 up to 1.46 and PHP 8.1 up to 8.5.
For more detailed information, see the [compatibility matrix](../COMPATIBILITY.md#compatibility).

## Security fixes

* Fixed a cross-site scripting (XSS) vulnerability in `Special:Browse` where Code-, Keyword- and External-identifier-typed property values were rendered without HTML escaping ([GHSA-6v7f-crgp-hhvp](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-6v7f-crgp-hhvp), [GHSA-r7xx-9gqh-q8p9](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-r7xx-9gqh-q8p9), [GHSA-2gfw-3hj2-wfq5](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-2gfw-3hj2-wfq5))
* Fixed a cross-site scripting (XSS) vulnerability on the schema page where a curator-authored schema description, tag, type link or JSON body was rendered without escaping ([GHSA-2xr9-cr6h-wr6m](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-2xr9-cr6h-wr6m))
* Fixed a cross-site scripting (XSS) vulnerability in `Special:Browse` where a property-group schema group name was rendered without escaping in the section heading ([GHSA-fp94-c5vh-cmhp](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-fp94-c5vh-cmhp))
* Fixed a cross-site scripting (XSS) vulnerability in `Special:FacetedSearch` where an exploration label from the profile schema was rendered as raw anchor content ([GHSA-3r5m-693w-jm33](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-3r5m-693w-jm33))
* Fixed a cross-site scripting (XSS) vulnerability in the tooltip module where wikitext-authorable `data-*` attribute values were rendered as raw HTML ([GHSA-4wj6-wmjp-pmhh](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-4wj6-wmjp-pmhh))
* Fixed a cross-site scripting (XSS) vulnerability in the property-value autocomplete where suggestion values from the API were inserted as raw HTML ([GHSA-mvpf-4wj8-2hwf](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-mvpf-4wj8-2hwf))
* Fixed a cross-site scripting (XSS) vulnerability in the optional extended `Special:Search` profile where the query string was rendered without escaping ([GHSA-375v-wx2r-4598](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-375v-wx2r-4598))
* Fixed a cross-site scripting (XSS) vulnerability in the non-table result printers where user-supplied separator parameters (`sep`, `propsep`, `valuesep`) were emitted unescaped in raw output ([GHSA-3v2c-r7rx-frc8](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-3v2c-r7rx-frc8))
* Fixed a cross-site scripting (XSS) vulnerability in the `Special:SMWAdmin` entity lookup where the `id` parameter was reflected unescaped ([GHSA-4fr3-ghqj-28wx](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-4fr3-ghqj-28wx))
* Fixed a cross-site scripting (XSS) vulnerability in the `json` result printer where the `default` parameter was emitted unescaped in file output ([GHSA-574v-j8hf-88h3](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-574v-j8hf-88h3))
* Fixed a missing authorization check in the `smwtask` API where a caller could force a store update or run queued jobs for a page they were not authorized to edit, completing the earlier fix for GHSA-jr78-w6w5-m8f8 ([GHSA-6wrc-864f-6vqp](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-6wrc-864f-6vqp), [GHSA-rjqv-6r83-pg8v](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-rjqv-6r83-pg8v))
* Fixed a missing authorization check where an editor without the edit-protection right could apply indefinite page protection through the `Is edit protected` annotation ([GHSA-9824-67pm-c29j](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-9824-67pm-c29j))
* Fixed a cross-site request forgery (CSRF) vulnerability in `Special:SMWAdmin` where state-changing maintenance actions could be triggered without a valid token ([GHSA-7f7v-2qf4-gfq3](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-7f7v-2qf4-gfq3))
* Fixed a denial-of-service vulnerability in query parsing where nested subqueries recursed before the configured depth limit was enforced; queries that nest more deeply than `$smwgQMaxDepth`, including long chains of explicit `AND`, are now rejected with an error ([GHSA-f8vw-wvm6-h2qq](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-f8vw-wvm6-h2qq))
* Fixed a denial-of-service vulnerability in `Special:Search` where compact `in:`/`has:` filters caused quadratic parsing work ([GHSA-j2r2-7qpg-p675](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-j2r2-7qpg-p675))
* Fixed a denial-of-service vulnerability in `Special:PropertyLabelSimilarity` where a non-positive limit loaded the entire property table into a quadratic comparison ([GHSA-wh64-r5x2-vjgf](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-wh64-r5x2-vjgf))
* Fixed a denial-of-service vulnerability where a `#set_recurring_event` limit could bypass `$smwgMaxNumRecurringEvents` ([GHSA-vxrm-2g9f-2vqh](https://github.com/SemanticMediaWiki/SemanticMediaWiki/security/advisories/GHSA-vxrm-2g9f-2vqh))

## Bug fixes

* Fixed the parser cache rejecting entries whose semantic data items carry options, such as every page saved after the store update ran (a `ParserCache: Unable to deserialize JSON` error and a full re-parse on the next view)
* Fixed a fatal error when formatting a monolingual text value with an empty text part
* Elasticsearch credentials are no longer shown in the settings list

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 7.0.0, ensure the SMW version in `composer.local.json` is `^7.3.1`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those who have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory
