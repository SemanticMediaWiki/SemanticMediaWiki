## SMW::SQLStore::Installer::AfterCreateTablesComplete

* Since: 2.5
* Description: Hook allows to add extra tables after the creation process as been finalized.
* Reference class: [`Installer.php`][Installer.php]

### Signature

```php
use Hooks;
use SMW\SQLStore\TableBuilder;
use Onoi\MessageReporter\MessageReporter;

Hooks::register( 'SMW::SQLStore::Installer::AfterCreateTablesComplete', function( TableBuilder $tableBuilder, MessageReporter $messageReporter ) {

	// Output details on the activity
	$messageReporter->reportMessage( '...' );

	// See documentation in the available TableBuilder interface
	$tableBuilder->create( ... );

	return true;
} );
```

[Installer.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/Installer.php