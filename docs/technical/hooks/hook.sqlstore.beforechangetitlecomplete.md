* Since: 2.1
* Description: Hook is called before change to a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::BeforeChangeTitleComplete', function( SQLStore $store, $oldTitle, $newTitle, $pageId, $redirectId ) {

	return true;
} );
```
