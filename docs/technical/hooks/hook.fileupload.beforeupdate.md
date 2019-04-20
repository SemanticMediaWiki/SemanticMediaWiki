* Since: 2.4
* Description: Hook to add extra annotations on a `FileUpload` event before the `Store` update is triggered
* Reference class: [`FileUpload.php`][FileUpload.php]

### Signature

```php
use Hooks;
use SMW\SemanticData;

Hooks::register( 'SMW::FileUpload::BeforeUpdate', function( $filePage, SemanticData $semanticData  ) {

	return true;
} );
```

[FileUpload.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Hooks/FileUpload.php