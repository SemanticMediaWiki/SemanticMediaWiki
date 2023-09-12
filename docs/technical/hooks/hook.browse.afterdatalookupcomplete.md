* Since: 2.3
* Description: Hook to extend the HTML with data displayed on `Special:Browse`
* Reference class: [`HtmlBuilder.php`][HtmlBuilder.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\Store;
use SMW\SemanticData;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Browse::AfterDataLookupComplete', function( Store $store, SemanticData $semanticData, &$html, &$extraModules ) {

	return true;
} );
```

[HtmlBuilder.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Specials/Browse/HtmlBuilder.php