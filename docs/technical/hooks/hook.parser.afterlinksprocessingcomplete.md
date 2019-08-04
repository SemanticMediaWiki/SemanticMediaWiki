## SMW::Parser::AfterLinksProcessingComplete

* Since: 3.1
* Description: Hook to add additional annotation parsing after `InTextAnnotationParser` has finished the processing of standard annotation links (e.g. `[[...::...]]`)
* Reference class: [`InTextAnnotationParser.php`][InTextAnnotationParser.php]

### Signature

```php
use Hooks;
use SMW\Parser\AnnotationProcessor;

Hooks::register( 'SMW::Parser::AfterLinksProcessingComplete', function( &$text, AnnotationProcessor $annotationProcessor ) {

	return true;
} );
```
## See also


- [`hook.parser.afterlinksprocessingcomplete.md`][hook.parser.afterlinksprocessingcomplete.md] to provide an example how the hook can be used to register a # hash tag parser

[PropertyRegistry.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/PropertyRegistry.php
[hook.parser.afterlinksprocessingcomplete.md]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.parser.afterlinksprocessingcomplete.md

[InTextAnnotationParser.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Parser/InTextAnnotationParser.php
