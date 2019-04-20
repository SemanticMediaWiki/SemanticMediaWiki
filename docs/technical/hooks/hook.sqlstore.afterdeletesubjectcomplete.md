* Since: 2.1
* Description: Hook is called after the deletion of a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;

Hooks::register( 'SMW::SQLStore::AfterDeleteSubjectComplete', function( SQLStore $store, $title ) {

	return true;
} );
```