<?php

/**
 * Static class for hooks handled by the Semantic MediaWiki extension.
 *
 * @since 1.7
 *
 * @file SemanticMediaWiki.hooks.php
 * @ingroup SMW
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class SMWHooks {

	/**
	 * Schema update to set up the needed database tables.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @since 1.7
	 *
	 * @param DatabaseUpdater $updater|null
	 *
	 * @return true
	 */
	public static function onSchemaUpdate( /* DatabaseUpdater */ $updater = null ) {
		// $updater can be null in MW 1.16.
		if ( !is_null( $updater ) ) {
			// Method was added in MW 1.19.
			if ( is_callable( array( $updater, 'addPostDatabaseUpdateMaintenance' ) ) ) {
				$updater->addPostDatabaseUpdateMaintenance( 'SMWSetupScript' );
			}
		}
		
		return true;
	}
	
}
