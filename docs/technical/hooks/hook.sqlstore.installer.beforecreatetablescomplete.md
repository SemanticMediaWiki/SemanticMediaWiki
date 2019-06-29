## SMW::SQLStore::Installer::BeforeCreateTablesComplete

* Since: 3.1
* Description: Hook to add additional table indices.
* Reference class: [`Installer.php`][Installer.php]

When using this hook, please make sure you understand the implications of modifying the standard table definition (e.g add auxiliary indices) which are not part of the core declaration and may alter performance expectations.

### Signature

```php
use Hooks;
use Onoi\MessageReporter\MessageReporter;

Hooks::register( 'SMW::SQLStore::Installer::BeforeCreateTablesComplete', function( array $tables, $messageReporter ) {

	// Modify the table definitions
} );
```

## See also

- [`hook.sqlstore.installer.beforecreatetablescomplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.sqlstore.installer.beforecreatetablescomplete.md) contains an example on how to modify table definitions (e.g. adding additional indices)

[Installer.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/SQLStore/Installer.php