<?php
/**
 * Sets up the storage backend currently selected in LocalSettings.php
 * (or the default MySQL store if no other store was selected). This
 * is equivalent to clicking the respective button on the page
 * Special:SMWAdmin. However, the latter may timeout if the setup involves
 * migrating a lot of existing data.
 *
 * Note: this file must be placed in MediaWiki's "maintenance" directory!
 *       or the MW_INSTALL_PATH environment variable must be set.
 *
 * Usage:
 * php SMW_refreshData.php [options...]
 *
 * @author Markus Krötzsch
 */

/**
 *no guarantees, but look in the usual place for commandLine.inc, so this
 * so it will work most of the time
 */

set_include_path( get_include_path() . PATH_SEPARATOR .  dirname(__FILE__) . '/../../../' . 'maintenance' );

/* user/password in LocalSettings probably don't have the rights we need,
 * so allow override
 */

$optionsWithArgs = array('user', 'password','b');
require_once ( getenv('MW_INSTALL_PATH') !== false
	? getenv('MW_INSTALL_PATH')."/maintenance/commandLine.inc"
	: 'commandLine.inc' );

if( isset( $options['user'] ) ) {
	global $wgDBuser;
	$wgDBuser = $options['user'];
}
if( isset( $options['password'] ) ) {
	global $wgDBuser;
	$wgDBpassword = $options['password'];
}

if ( array_key_exists( 'b', $options ) ) {
	global $smwgDefaultStore;
	$smwgDefaultStore = $options['b'];
	print "\nSelected storage $smwgDefaultStore for update!\n\n";
}


global $smwgIP;
if (! isset($smwgIP)) 
     $smwgIP = dirname(__FILE__) . '/..';

require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

smwfGetStore()->setup();
wfRunHooks('smwInitializeTables');

print "\n\nDone.\n";


