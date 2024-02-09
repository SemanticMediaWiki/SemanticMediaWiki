* Since: 3.1
* Description: Hook to extend the `SemanticData` object before the update is completed. (`SMWStore::updateDataBefore` was deprecated with 3.1)
* Reference class: [`Store.php`][Store.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\Store;
use SMW\SemanticData;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::Store::BeforeDataUpdateComplete', function( Store $store, SemanticData $semanticData ) {

	return true;
} );
```

[Store.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Store.php
