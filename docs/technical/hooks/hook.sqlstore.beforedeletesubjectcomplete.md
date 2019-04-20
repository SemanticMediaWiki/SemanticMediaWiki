* Since: 2.1
* Description: Hook is called before the deletion of a subject is completed
* Reference class: `SMW_SQLStore3_Writers.php`

### Signature

```php
use Hooks;
use SMW\SQLStore\SQLStore;

Hooks::register( 'SMW::SQLStore::BeforeDeleteSubjectComplete', function( SQLStore $store, $title ) {

	return true;
} );
```