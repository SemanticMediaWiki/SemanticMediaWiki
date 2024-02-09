* Since: 3.0
* Description: Hook allows to add extra preferences that are ordered on the Semantic MediaWiki user preference tab.
* Reference class: [`GetPreferences.php`][GetPreferences.php]

### Signature

```php
use MediaWiki\MediaWikiServices;
use User;

MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::GetPreferences', function( User $user, &$preferences ) {

	return true;
} );
```

[GetPreferences.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/MediaWiki/Hooks/GetPreferences.php