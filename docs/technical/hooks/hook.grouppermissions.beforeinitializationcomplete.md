## SMW::GroupPermissions::BeforeInitializationComplete

**Removed in SMW 7.0.** Permission rights and group assignments are now declared in `extension.json` using the `AvailableRights` and `GroupPermissions` keys, which are processed by MediaWiki before any hook can run. Extensions that need to modify SMW's permissions should use MediaWiki's standard `$wgGroupPermissions` override in `LocalSettings.php` instead.

* Since: 3.2
* Removed: 7.0
* Description: Hook to provide a possibility to modify Semantic MediaWiki's permissions settings before the initialization is completed.
* Reference class: [`GroupPermissions.php`][GroupPermissions.php]

[GroupPermissions.php]:https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/GroupPermissions.php
