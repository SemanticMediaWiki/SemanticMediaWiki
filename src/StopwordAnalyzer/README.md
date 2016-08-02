# StopwordAnalyzer

This interface provides the means to filter specific frequent words (or characters) from a text corpus.

```php

use Onoi\Tesa\SanitizerFactory;

$sanitizerFactory = new SanitizerFactory();

$stopwordAnalyzer = $sanitizerFactory->newStopwordAnalyzerByLanguage(
	'en'
);

$stopwordAnalyzer->isStopWord( 'foo' );

```

## CdbStopwordAnalyzer

`CdbStopwordAnalyzer` uses [cdb][cdb] as storage backend to allow for an instant access to a list of
stopwords on a per language basis.

Adding a new set of stopwords to a language only requires to place a JSON file into the the `/data` folder
and extend the `CdbStopwordAnalyzerTest` which ensures that listed languages and their JSON files are
converted into a corresponding cdb file.

```
{
	"@source": "...",
	"version": "0.1",
	"list":[
		"a",
		"about",
		"..."
	]
}
```

[cdb]: https://en.wikipedia.org/wiki/Cdb_(software)