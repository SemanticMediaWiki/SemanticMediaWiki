[Usage][section:usage] | [Settings][section:config] | [Technical notes][section:technical] | [FAQ][section:faq]

To use the `ElasticStore` (hereby Elasticsearch) as a drop-in replacement for the existing `SQLStore` based `QueryEngine` the following settings require some changes:

- Set `$GLOBALS['smwgDefaultStore'] = 'SMWElasticStore';`
- Set `$GLOBALS['smwgElasticsearchEndpoints'] = [ ... ];` (see the [documentation][es:conf:hosts] for how to maintain inline or extended host parameters as it takes the same attributes as outlined in the official documentation, or see a [configuration example][conf:example])
- Run `php setupStore.php` or `php update.php`
- Rebuild the index using `php rebuildElasticIndex.php`

It is recommended to consult the [official][es:conf] documentation for Elasticsearch specific settings and configurations.

## Topics

- The [replication section][section:replication] contains details about how indexing is expected work in connection with Semantic MediaWiki and any peculiarities in regards to the replication with Elasticsearch. It further provides a passage on indexing "unstructured" content from either an article or file.
- The [search section][section:search] highlights the use of the `#ask` syntax in combination with the Elasticsearch specific query execution.

[es:conf]: https://www.elastic.co/guide/en/elasticsearch/reference/6.1/system-config.html
[es:conf:hosts]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/configuration.html
[es:php-api]: https://www.elastic.co/guide/en/elasticsearch/client/php-api/6.0/_installation_2.html
[conf:example]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:usage]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/usage.md
[section:config]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/config.md
[section:technical]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/technical.md
[section:faq]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/faq.md
[section:replication]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/replication.md
[section:search]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/docs/search.md
