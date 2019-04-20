* Since: 2.1
* Description: Hook is called before change to a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;

Hooks::register( 'SMW::SQLStore::BeforeChangeTitleComplete', function( SQLStore $store, $oldTitle, $newTitle, $pageId, $redirectId ) {

	return true;
} );
```