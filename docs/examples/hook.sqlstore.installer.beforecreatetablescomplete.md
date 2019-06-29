## SMW::SQLStore::Installer::BeforeCreateTablesComplete

### Adding primary keys

Demonstrates how to add primary keys ([#3559][issue-3559]) to Semantic MediaWiki table definitions.

```php
use Hooks;

Hooks::register( 'SMW::SQLStore::Installer::BeforeCreateTablesComplete', function( array $tables, $messageReporter ) {

	// #3559
	// Incomplete list to only showcase how to modify the table definition
	$primaryKeys = [
		'smw_di_blob'     => 'p_id,s_id,o_hash',
		'smw_di_bool'     => 'p_id,s_id,o_value',
		'smw_di_uri'      => 'p_id,s_id,o_serialized',
		'smw_di_coords'   => 'p_id,s_id,o_serialized',
		'smw_di_wikipage' => 'p_id,s_id,o_id',
		'smw_di_number'   => 'p_id,s_id,o_serialized',

		// smw_fpt ...

		'smw_prop_stats'  => 'p_id',
		'smw_query_links' => 's_id,o_id'
	];

	/**
	 * @var \Onoi\MessageReporter\MessageReporter
	 */
	$messageReporter->reportMessage( "Setting primary indices.\n" );

	/**
	 * @var \SMW\SQLStore\TableBuilder\Table[]
	 */
	foreach ( $tables as $table ) {
		if ( isset( $primaryKeys[$table->getName()] ) ) {
			$table->setPrimaryKey( $primaryKeys[$table->getName()] );
		}
	}

	$messageReporter->reportMessage( "\ndone.\n" );

} );
```

## See also

- [`hook.sqlstore.installer.beforecreatetablescomplete`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/hooks/hook.sqlstore.installer.beforecreatetablescomplete.md)

[issue-3559]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/3559