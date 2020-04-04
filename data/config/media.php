<?php

/**
 * Convenience settings to extend Semantic MediaWiki with media related functionality.
 *
 * @since 3.2
 */

$properties = [ '_MIME', '_MEDIA', '_ATTCH_LINK' ];

return [

	/**
	 * @see $smwgPageSpecialProperties
	 */
	'smwgPageSpecialProperties' => array_merge( $GLOBALS['smwgPageSpecialProperties'], $properties )
];