* Since: 3.1
* Description: Hook to define whether a revision is approved or needs to be suppressed. For example, the `latestRevID` contains an ID that is not the revision that is approved an should not be used for the `SemanticData` representation during an update.
* Reference class: [`RevisionGuard.php`][RevisionGuard.php]

### Signature

```php
use Hooks;

Hooks::register( 'SMW::RevisionGuard::IsApprovedRevision', function( $title, $latestRevID ) {

	// If you need to decline an update (aka is not approved)
	// return false;

	return true;
} );
```

## See also

- See the [`SemanticApprovedRevs`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs) extension for how to use the hook

[RevisionGuard.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/RevisionGuard.php