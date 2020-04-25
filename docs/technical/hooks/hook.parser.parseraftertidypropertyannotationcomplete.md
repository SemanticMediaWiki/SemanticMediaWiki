## SMW::Parser::ParserAfterTidyPropertyAnnotationComplete

* Since: 3.2
* Description: Provides a method to add additional `PropertyAnnotator` as part of the `ParserAfterTidy` after default annotators have been executed
* Reference class: [`ParserAfterTidy.php`][ParserAfterTidy.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::Parser::ParserAfterTidyPropertyAnnotationComplete', function( $propertyAnnotator, $parserOutput ) {

	$fooAnnotator = new FooAnnotator(
		$propertyAnnotator
	);

	$fooAnnotator->addAnnotation();

	return true;
} );
```

[ParserAfterTidy.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Hooks/ParserAfterTidy.php
