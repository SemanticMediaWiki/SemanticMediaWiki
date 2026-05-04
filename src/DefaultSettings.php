<?php

/**
 * DO NOT EDIT!
 *
 * The following default settings are to be used by the extension itself,
 * please modify settings in the LocalSettings file.
 *
 * Most settings should be made in LocalSettings.php after the call to
 * wfLoadExtension( 'SemanticMediaWiki' ).
 *
 * @codeCoverageIgnore
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

return ( static function (): array {
	SemanticMediaWiki::setupDefines();
	return [

		# ##
		# Sets whether the > and < comparators should be strict or not. If they are strict,
		# values that are equal will not be accepted.
		#
		# @since 1.5.3
		##
		'smwStrictComparators' => false,

	];
} )();
