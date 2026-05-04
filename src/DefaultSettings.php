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
	$smwgIP = dirname( __DIR__ ) . '/';
	return [

		# ##
		# This is the path to your installation of Semantic MediaWiki as seen on your
		# local filesystem. Used against some PHP file path issues.
		#
		# @since 1.0
		##
		'smwgIP' => $smwgIP,
		#
		# @since 2.5
		##
		'smwgExtraneousLanguageFileDir' => $smwgIP . '/i18n/extra',
		'smwgServicesFileDir' => $smwgIP . '/src/Services',
		'smwgMaintenanceDir' => $smwgIP . '/maintenance',
		'smwgDir' => $smwgIP,
		# #

		###
		# Configuration directory
		# @see #3506
		#
		# The maintained directory needs to be writable in order for configuration
		# information to be stored persistently and be accessible for Semantic
		# MediaWiki throughout its operation.
		#
		# You may assign the same directory as in `wgUploadDirectory` (e.g
		# $smwgConfigFileDir = $wgUploadDirectory;) or select an entire different
		# location. The default location is the Semantic MediaWiki extension root.
		#
		# During its operation it may contain:
		#  - `.smw.json`
		#  - `.smw.maintenance.json`
		#
		# @since 3.0
		##
		'smwgConfigFileDir' => $smwgIP,
		# #

		###
		# Content import
		#
		# Controls the content import directory and version that is expected to be
		# imported during the setup process.
		#
		# For all legitimate files in `smwgImportFileDirs`, the import is initiated
		# if the `smwgImportReqVersion` compares with the declared version in the file.
		#
		# In case `smwgImportReqVersion` is maintained with `false` then the import
		# is going to be disabled.
		#
		# @since 2.5
		##
		'smwgImportFileDirs' => [ 'smw' => $smwgIP . '/data/import' ],
		# #

		###
		# If you already have custom namespaces on your site, insert
		#    	'smwgNamespaceIndex' => ???,
		# into your LocalSettings.php *before* including this file. The number ??? must
		# be the smallest even namespace number that is not in use yet. However, it
		# must not be smaller than 100.
		#
		# @since 1.6
		##
		# 'smwgNamespaceIndex' => 100,
		##

		###
		# Sets whether the > and < comparators should be strict or not. If they are strict,
		# values that are equal will not be accepted.
		#
		# @since 1.5.3
		##
		'smwStrictComparators' => false,

		# ##
		# -- FEATURE IS DISABLED --
		# If you want to import ontologies, you need to install RAP,
		# a free RDF API for PHP, see
		#     http://wifo5-03.informatik.uni-mannheim.de/bizer/rdfapi/index.html
		# The following is the path to your installation of RAP
		# (the directory where you extracted the files to) as seen
		# from your local filesystem. Note that ontology import is
		# highly experimental at the moment, and may not do what you
		# extect.
		#
		# @since 1.0
		##
		// 	'smwgRAPPath' => $smwgIP . 'libs/rdfapi-php',
		// 	'smwgRAPPath' => '/another/example/path/rdfapi-php',
		##

	];
} )();
