# Tesa (text sanitizer)

[![Build Status](https://secure.travis-ci.org/onoi/tesa.svg?branch=master)](http://travis-ci.org/onoi/tesa)
[![Code Coverage](https://scrutinizer-ci.com/g/onoi/tesa/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/onoi/tesa/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/onoi/tesa/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/onoi/tesa/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/onoi/tesa/version.png)](https://packagist.org/packages/onoi/tesa)
[![Packagist download count](https://poser.pugx.org/onoi/tesa/d/total.png)](https://packagist.org/packages/onoi/tesa)
[![Dependency Status](https://www.versioneye.com/php/onoi:tesa/badge.png)](https://www.versioneye.com/php/onoi:tesa)

The library contains a small collection of helper classes to support sanitization
of text or string elements of arbitrary length with the aim to improve
search match confidence during a query execution that is required by [Semantic MediaWiki][smw]
project and is deployed independently.

## Requirements

- PHP 5.3 / HHVM 3.5 or later
- Recommended to enable the [ICU][icu] extension

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
use Onoi\Tesa\SanitizerFactory;
use Onoi\Tesa\Transliterator;
use Onoi\Tesa\Sanitizer;

$sanitizerFactory = new SanitizerFactory();

$sanitizer = $sanitizerFactory->newSanitizer( 'A string that contains ...' );

$sanitizer->reduceLengthTo( 200 );
$sanitizer->toLowercase();

$sanitizer->replace(
	array( "'", "http://", "https://", "mailto:", "tel:" ),
	array( '' )
);

$sanitizer->setOption( Sanitizer::MIN_LENGTH, 4 );
$sanitizer->setOption( Sanitizer::WHITELIST, array( 'that' ) );

$sanitizer->applyTransliteration(
	Transliterator::DIACRITICS | Transliterator::GREEK
);

$text = $sanitizer->sanitizeWith(
	$sanitizerFactory->newGenericTokenizer(),
	$sanitizerFactory->newNullStopwordAnalyzer(),
	$sanitizerFactory->newNullSynonymizer()
);

```

- `SanitizerFactory` is expected to be the sole entry point for services and instances
  when used outside of this library
- `IcuWordBoundaryTokenizer` is a preferred tokenizer in case the [ICU][icu] extension is available
- `NGramTokenizer` is provided to increase CJK match confidence in case the
  back-end does not provide an explicit ngram tokenizer
- `StopwordAnalyzer` together with a `LanguageDetector` is provided as a means to
  reduce ambiguity of frequent "noise" words from a possible search index
- `Synonymizer` currently only provides an interface

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

- 0.1.0 Initial release (2016-08-07)
 - Added `SanitizerFactory` with support for a
 - `Tokenizer`, `LanguageDetector`, `Synonymizer`, and `StopwordAnalyzer` interface

## Acknowledgments

- The `Transliterator` uses the same diacritics conversion table as http://jsperf.com/latinize
  (except the German diaeresis ä, ü, and ö)
- The stopwords used by the `StopwordAnalyzer` have been collected from different sources, each `json`
  file identifies its origin
- `CdbStopwordAnalyzer` relies on `wikimedia/cdb` to avoid using an external database or cache
  layer (with extra stopwords being available [here](https://github.com/6/stopwords-json))
- `JaTinySegmenterTokenizer` is based on the work of Taku Kudo and his [tiny_segmenter.js](http://chasen.org/~taku/software/TinySegmenter)
- `TextCatLanguageDetector` uses the [`wikimedia/textcat`][textcat] library to make predictions about a language

## License

[GNU General Public License 2.0 or later][license].

[composer]: https://getcomposer.org/
[contributors]: https://github.com/onoi/tesa/graphs/contributors
[license]: https://www.gnu.org/copyleft/gpl.html
[travis]: https://travis-ci.org/onoi/tesa
[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/
[icu]: http://php.net/manual/en/intro.intl.php
[textcat]: https://github.com/wikimedia/wikimedia-textcat
