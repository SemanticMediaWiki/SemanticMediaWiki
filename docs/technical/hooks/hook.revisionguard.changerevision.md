* Since: 3.1
* Description: Hook allows to forcibly change a revision used during content parsing as in case of the `UpdateJob` execution or when running `rebuildData.php`.
* Reference class: [`RevisionGuard.php`][RevisionGuard.php]

If you do alter a revision, please log the event and make it visible to a user (or administrator) that it was changed.

### Signature

```php
use Hooks;

Hooks::register( 'SMW::RevisionGuard::ChangeRevision', function( $title, &$revision ) {

	// Set a revision
	// $revision = \Revision::newFromId( $id );

	return true;
} );
```

## See also

- See the [`SemanticApprovedRevs`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs) extension for how to use the hook

[RevisionGuard.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/RevisionGuard.php