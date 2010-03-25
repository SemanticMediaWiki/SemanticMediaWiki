<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) die();

global $smwgIP;
include_once( $smwgIP . 'languages/SMW_LanguageDe.php' );

/**
 * Translations for formal German are just the same as for German regarding
 * the core notions of SMW. Hence this class just extends SMWLanguageDe.
 *
 * @author Markus Krötzsch
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageDe_formal extends SMWLanguageDe {
}
