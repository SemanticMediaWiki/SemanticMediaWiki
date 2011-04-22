<?php

/**
 * File for backward compatibility with pre SMW 1.5.1.
 * This used to be the main entrypoint for SMW, now moved to
 * SemanticMediaWiki.php
 *
 * @file SMW_Settings.php
 * @ingroup SMW
 *
 * @author Jeroen De Dauw
 */

if ( !defined( 'MEDIAWIKI' ) ) {
  die( "This file is part of the Semantic MediaWiki extension. It is not a valid entry point.\n" );
}

// Could be promoted to Warning one version before really removing this file
trigger_error( 'Outdated SMW entry point. Use SemanticMediaWiki/SemanticMediaWiki.php instead of SemanticMediaWiki/includes/SMW_Setup.php', E_USER_NOTICE );

require_once dirname( __FILE__ ) . '/../SemanticMediaWiki.php';