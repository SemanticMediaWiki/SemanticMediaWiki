## SMW::Maintenance::AfterUpdateEntityCollationComplete

* Since: 3.1
* Description: Hook to allow to run other updates after the `updateEntityCollection.php` script has finished processing the update of entity collation changes
* Reference class: [`updateEntityCollation.php`][updateEntityCollation.php]

### Signature

```php
use Hooks;
use SMW\Store;
use Onoi\MessageReporter\MessageReporter;

Hooks::register( 'SMW::Maintenance::AfterUpdateEntityCollationComplete', function( Store $store, MessageReporter $messageReporter ) {

	return true;
} );
```

## See also

- See the [`ElasticFactory.php`][ElasticFactory.php] for an implementation example

[updateEntityCollation.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/maintenance/updateEntityCollation.php
[ElasticFactory.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Elastic/ElasticFactory.php