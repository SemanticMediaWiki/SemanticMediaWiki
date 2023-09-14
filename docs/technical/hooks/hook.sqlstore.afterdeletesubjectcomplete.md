* Since: 2.1
* Description: Hook is called after the deletion of a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

### Signature

```php
use MediaWiki\MediaWikiServices;
use SMW\SQLStore\SQLStore;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::AfterDeleteSubjectComplete', function( SQLStore $store, $title ) {

	return true;
} );
```
