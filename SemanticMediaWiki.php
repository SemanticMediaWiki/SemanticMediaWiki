<?php

/**
 * Main entry point for the Semantic MediaWiki extension.
 */

/**
 * This documentation group collects source code files belonging to Semantic
 * MediaWiki.
 *
 * For documenting extensions of SMW, please do not use groups starting with
 * "SMW" but make your own groups instead. Browsing at
 * http://semantic-mediawiki.org/doc/  is assumed to be easier this way.
 * 
 * @defgroup SMW Semantic MediaWiki
 */

// Include the Validator extension if that hasn't been done yet, since it's required for SMW to work.
if ( !defined( 'Validator_VERSION' ) ) {
	@include_once( dirname( __FILE__ ) . '/../Validator/Validator.php' );
}

// Only initialize the extension when all dependencies are present.
if ( !defined( 'Validator_VERSION' ) ) {
	die( '<b>Error:</b> You need to have <a href="http://www.mediawiki.org/wiki/Extension:Validator">Validator</a> installed in order to use <a href="http://www.semantic-mediawiki.org">Semantic MediaWiki</a>.<br />' );
}

require_once dirname( __FILE__ ) . '/SMW_Settings.php';

// Registration of the extension credits, see Special:Version.
$wgExtensionCredits['semantic'][] = array(
	'path' => __FILE__,
	'name' => 'Semantic MediaWiki',
	'version' => SMW_VERSION,
	'author' => '[http://korrekt.org Markus&#160;Krötzsch], [http://simia.net Denny&#160;Vrandecic] and [http://www.mediawiki.org/wiki/User:Jeroen_De_Dauw Jeroen De Dauw]. Maintained by [http://www.aifb.kit.edu/web/Wissensmanagement/en AIFB Karlsruhe].',
	'url' => 'http://semantic-mediawiki.org',
	'descriptionmsg' => 'smw-desc'
);

// A flag used to indicate SMW supports Validator style parameter definitions and validation in the SMWResultPrinter class.
define( 'SMW_SUPPORTS_VALIDATOR', true );
