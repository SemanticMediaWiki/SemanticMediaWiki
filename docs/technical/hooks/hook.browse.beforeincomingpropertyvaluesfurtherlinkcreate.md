* Since: 2.3
* Description: Hook to replace the standard `SearchByProperty` with a custom link in `Special:Browse` to an extended list of results (return `false` to replace the link)
* Reference class: [`HtmlBuilder.php`][HtmlBuilder.php]

### Signature

```php
use Hooks;
use SMW\DIProperty;
use SMW\DIWikiPage;

\Hooks::register( 'SMW::Browse::BeforeIncomingPropertyValuesFurtherLinkCreate', function( DIProperty $property, DIWikiPage $subject, &$propertyValue ) {

	// return `false` to replace the link

	return true;
} );
```

## See also

- For a usage example, see the [`SemanticCite`](https://github.com/SemanticMediaWiki/SemanticCite) extension

[HtmlBuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Specials/Browse/HtmlBuilder.php