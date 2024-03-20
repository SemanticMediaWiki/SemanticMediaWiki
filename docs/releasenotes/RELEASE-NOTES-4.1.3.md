# Semantic MediaWiki 4.1.3

Released on February 17th, 2023.

## Summary

This is a [patch release](../RELEASE-POLICY.md). Thus it contains only bug fixes. No new features or breaking changes.

This release contains security patches and improves support for modern PHP and MediaWiki.
Upgrading is recommended for all users.

## Breaking changes
* This version of SemanticMediaWiki requires Elasticsearch 7.10, with a change of configuration (see below) - this is only a problem if you enabled ElasticStore

## Changes

* Fixed several XSS issues
* Improved support for PHP 8.1 and above
* Improved support for MediaWiki 1.39 and above
* Various minor performance improvements, especially for multi-database setups
* Fixed Special:PageProperty (thanks thomas-topway-it)
* Improved compatibility with OpenSearch and recent versions of ElasticSearch
* Improved PostgreSQL compatibility
* Added ability to disable the upgrade key check via the `smwgIgnoreUpgradeKeyCheck` setting
* Improved various interface messages
* Translation updates

## Contributors

Top Contributors

* [Niklas Laxström](https://github.com/Nikerabbit) from [TranslateWiki](https://translatewiki.net)
* [Jeroen De Dauw](https://EntropyWins.wtf) from [Professional Wiki](https://professional.wiki/)
* [Máté Szabó](https://github.com/mszabo-wikia) from Fandom

Code Contributors

* Máté Szabó
* Niklas Laxström
* Jeroen De Dauw
* Marijn van Wezel
* H. C. Kruse
* thomas-topway-it
* Bernhard Krabina
* Tomasz Tomalak
* paladox
* Abijeet
* Abijeet Patro
* C. Scott Ananian
* Jon Harald Søby
* Michael Erdmann
* Simon Stier
* Someone
* Winston Sung
* Youri vd Bogert
* Yvar
* Zoran Dori
* wgevaert
* Łukasz Harasimowicz
* 星河

## Upgrading

No need to run "update.php" or any other migration scripts.

**Get the new version via Composer:**

* Step 1: if you are upgrading from SMW older than 4.0.0, ensure the SMW version in `composer.json` is `^4.1.3`
* Step 2: run composer in your MediaWiki directory: `composer update --no-dev --optimize-autoloader`

**Get the new version via Git:**

This is only for those that have installed SMW via Git.

* Step 1: do a `git pull` in the SemanticMediaWiki directory
* Step 2: run `composer update --no-dev --optimize-autoloader` in the MediaWiki directory

**Upgrading Elasticsearch:**
This version of SMW uses Elasticsearch 7.10.2 (if you have enabled it). If you are using Elasticsearch as a backend, then you need to perform the following changes:
* Step 1: Upgrade your version of Elasticsearch to 7.10.2 (if you are using docker that should work without any other action than changing the version number)
* Step 2: Make sure your version of the elasticsearch package is right, you might want to add it to the composer.local.json: `COMPOSER=composer.local.json composer require elasticsearch/elasticsearch "7.17.2"`
* Step 3: Add $smwgElasticsearchCredentials after $smwgElasticsearchEndpoints:

```
$smwgElasticsearchEndpoints = [ 
    [
        'host' => $elasticSearchHost,
        'port' => 9200,
        'scheme' => 'http'
    ]
];

$smwgElasticsearchCredentials = [
    'user' => $elastic_user,
    'pass' => $elastic_password,
];
```


