* Since: 2.2
* Description: Hook to extend the magic words list that the `InTextAnnotationParser` should inspect on a given text section
* Reference class: [`InTextAnnotationParser.php`][InTextAnnotationParser.php]

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;

Hooks::register( 'SMW::Parser::BeforeMagicWordsFinder', function( array &$magicWords ) {

	return true;
} );
```
## See also

- See the [`SemanticBreadcrumbLinks`](https://github.com/SemanticMediaWiki/SemanticBreadcrumbLinks) extension for how the hook can be used

[InTextAnnotationParser.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Parser/InTextAnnotationParser.php