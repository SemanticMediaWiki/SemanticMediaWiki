<?php

/**
 * File for backward compatibility with pre SMW 1.5.1.
 * This used to be the main entrypoint for SMW, now moved to SemanticMediaWiki.php
 *
 * @file SMW_Settings.php
 * @ingroup SMW
 *
 * @author Jeroen De Dauw
 */

wfWarn( 'Use of outdated SMW entry point. Use SemanticMediaWiki/SemanticMediaWiki.php instead of SemanticMediaWiki/includes/SMW_Setup.php' );

require_once dirname( __FILE__ ) . '/../SMW_Settings.php';