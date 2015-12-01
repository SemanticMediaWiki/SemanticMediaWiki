# Tesa (text sanitizer)

[![Build Status](https://secure.travis-ci.org/onoi/tesa.svg?branch=master)](http://travis-ci.org/onoi/tesa)
[![Code Coverage](https://scrutinizer-ci.com/g/onoi/tesa/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/onoi/tesa/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/onoi/tesa/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/onoi/tesa/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/onoi/tesa/version.png)](https://packagist.org/packages/onoi/tesa)
[![Packagist download count](https://poser.pugx.org/onoi/tesa/d/total.png)](https://packagist.org/packages/onoi/tesa)
[![Dependency Status](https://www.versioneye.com/php/onoi:tesa/badge.png)](https://www.versioneye.com/php/onoi:tesa)

Deployed independent from the [Semantic MediaWiki][smw] project. The library contains a small collection of
helper classes to support sanitization of text or string elements of arbitrary length with the aim to improve
search match confidence during a query execution.

This library includes:

- `Transliterator` to help convert diacritics or greek letters into a romanized version
- `StopwordAnalyzer` to manage a list of registered stopwords
- `Tokenizer` to split a text by common punctuation

## Requirements

PHP 5.3 / HHVM 3.5 or later

## Installation

The recommended installation method for this library is by adding
the following dependency to your [composer.json][composer].

```json
{
	"require": {
		"onoi/tesa": "~0.1"
	}
}
```

## Usage

```php
$sanitizer = new Sanitizer( $string );
$sanitizer->reduceLengthTo( '200' );

$sanitizer->toLowercase();

$sanitizer->replace(
	array( "'", "http://", "https://", "mailto:", "tel:" ),
	array( '' )
);

$sanitizer->applyTransliteration(
	Transliterator::DIACRITICS | Transliterator::GREEK
);
```

```php
use Onoi\Cache\CacheFactory;

$cacheFactory = new CacheFactory();
$cache = $cacheFactory->newMediaWikiCache( wfGetCache( 'redis' ) );

$stopwordAnalyzer = new StopwordAnalyzer( $cache );
$stopwordAnalyzer->loadListBy( StopwordAnalyzer::DEFAULT_STOPWORDLIST );

$stopwordAnalyzer = new StopwordAnalyzer( $cache );

$sanitizer = new Sanitizer( $string );
$sanitizer->toLowercase();

$string = $sanitizer->sanitizeBy(
	$stopwordAnalyzer
);
```

- It is recommended that the `StopwordAnalyzer` is invoked using a responsive cache provider (such as
APC or redis) to minimize any latency when the stopword list is loaded.

### Data sources

- The `Transliterator` used diacritics conversion table has been copied from http://jsperf.com/latinize.
- The stopwords used by the `StopwordAnalyzer` have been collected from different sources where each `json`
  file identifies its origin.

## Contribution and support

If you want to contribute work to the project please subscribe to the
developers mailing list and have a look at the [contribution guidelinee](/CONTRIBUTING.md). A list
of people who have made contributions in the past can be found [here][contributors].

* [File an issue](https://github.com/onoi/tesa/issues)
* [Submit a pull request](https://github.com/onoi/tesa/pulls)

## Tests

The library provides unit tests that covers the core-functionality normally run by the
[continues integration platform][travis]. Tests can also be executed manually using the
`composer phpunit` command from the root directory.

## Release notes

- 0.1.0 Initial release (2015-11-??)

## License

[GNU General Public License 2.0 or later][license].

[composer]: https://getcomposer.org/
[contributors]: https://github.com/onoi/tesa/graphs/contributors
[license]: https://www.gnu.org/copyleft/gpl.html
[travis]: https://travis-ci.org/onoi/tesa
[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/
