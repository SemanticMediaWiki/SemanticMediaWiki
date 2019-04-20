* Since: 3.1
* Description: Hook allows to forcibly change the file version used.
* Reference class: [`RevisionGuard.php`][RevisionGuard.php]

If you do alter a file, please log the event and make it visible to a user (or administrator) that it was changed.

### Signature

```php
use Hooks;

Hooks::register( 'SMW::RevisionGuard::ChangeFile', function( $title, &$file ) {

	// $file = ...;

	return true;
} );
```

## See also

- See the [`SemanticApprovedRevs`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs) extension for how to use the hook

[RevisionGuard.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/RevisionGuard.php