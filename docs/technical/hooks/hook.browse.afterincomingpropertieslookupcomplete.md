* Since: 2.3
* Description: Hook to extend the incoming properties display for `Special:Browse`
* Reference class: [`HtmlBuilder.php`][HtmlBuilder.php]

### Signature

```php
use Hooks;
use SMW\Store;
use SMW\SemanticData;
use SMW\RequestOptions

Hooks::register( 'SMW::Browse::AfterIncomingPropertiesLookupComplete', function( Store $store, SemanticData $semanticData, RequestOptions $requestOptions ) {

	return true;
} );
```

## See also

- For a usage example, see the [`SemanticCite`](https://github.com/SemanticMediaWiki/SemanticCite) extension

[HtmlBuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Specials/Browse/HtmlBuilder.php