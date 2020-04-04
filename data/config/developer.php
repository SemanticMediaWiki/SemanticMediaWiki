<?php

/**
 * Default settings mostly used in connection with a development environment and
 * it not meant to be used in production.
 *
 * @since 3.2
 */

error_reporting(E_STRICT|E_ALL);
ini_set("display_errors", 1);

return [

	/**
	 *  MediaWiki specific settings to be used within a development environment
	 */
	'wgShowExceptionDetails' => true,
	'wgDevelopmentWarnings' => true,
	'wgShowSQLErrors' => true,
	'wgDebugDumpSql' => true,
	'wgShowDBErrorBacktrace' => true,

	/**
	 * @see https://www.mediawiki.org/wiki/Debugging_toolbar
	 *
	 * A utility for developers that displays debug information about a MediaWiki
	 * page at the bottom of the browser window.
	 */
	'wgDebugToolbar' => true,

	/**
	 * Semantic MediaWiki related
	 */

	/**
	 * @see $smwgIgnoreExtensionRegistrationCheck
	 */
	'smwgIgnoreExtensionRegistrationCheck' => true,

	/**
	 * @see $smwgDefaultLoggerRole
	 *
	 * You never want this role to be enabled in production because it will create
	 * large log files while monitoring SMW related activities in detail.
	 */
	'smwgDefaultLoggerRole' => 'developer',

	/**
	 * @see $smwgJobQueueWatchlist
	 */
	'smwgJobQueueWatchlist' => [
	    'smw.update',
	    'smw.fulltextSearchTableUpdate',
	    'smw.changePropagationUpdate',
	    'smw.changePropagationClassUpdate',
	    'smw.changePropagationDispatch',
	    'smw.elasticIndexerRecovery',
	    'smw.elasticFileIngest'
	]

];