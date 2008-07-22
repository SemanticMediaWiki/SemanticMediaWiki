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
 * -b        <backend>    Execute the operation for the storage backend of the given name 
 * -user     <dbuser>     Database user account to use for chaning DB layout
 * -password <dbpassword> Password for user account
 * NOTE: specifying user credentials in a command line call will usually store them
 * within the shell history file. For security, provide credentials in Adminssetings.php
 * instead and ensure that your text editor does not create world-readable backup copies
 * when modifying this file.
 *
 * --delete   Delete all SMW data, uninstall the selected storage backend. This is useful
 *            when moving to a new storage engine, and in the rare case of unsinstalling
 *            SMW. Deleted data can be recreated using this script (setup) and 
 *            SMW_refreshData.php but this may take some time.
 * @author Markus Krötzsch
 */

/**
 * no guarantees, but look in the usual place for commandLine.inc, so this
 * so it will work most of the time
 */

$optionsWithArgs = array( 'b', 'user', 'password');

require_once ( getenv('MW_INSTALL_PATH') !== false
    ? getenv('MW_INSTALL_PATH')."/maintenance/commandLine.inc"
    : dirname( __FILE__ ) . '/../../../maintenance/commandLine.inc' );
require_once("$IP/maintenance/counter.php");

global $smwgDefaultStore;

/* user/password in LocalSettings probably don't have the rights we need,
 * so allow override
 * Note: the preferred method is to use AdminSettings.php to provide such credentials
 */
if( isset( $options['user'] ) ) {
	global $wgDBuser;
	$wgDBuser = $options['user'];
}
if( isset( $options['password'] ) ) {
	global $wgDBuser;
	$wgDBpassword = $options['password'];
}

if ( array_key_exists( 'b', $options ) ) {
	if ($smwgDefaultStore != $options['b']) {
		$alternativestore = true;
	} else {
		$alternativestore = false;
	}
	$smwgDefaultStore = $options['b'];
	print "\nSelected storage " . $smwgDefaultStore . " for update!\n\n";
}


global $smwgIP;
if (! isset($smwgIP)) 
     $smwgIP = dirname(__FILE__) . '/..';

require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

if (  array_key_exists( 'delete', $options ) ) {
	print "\n  Deleting all stored data for $smwgDefaultStore completely!\n  \n\n";
	if ( $alternativestore ) {
		print "  This store is currently not used by SMW. Deleting it\n  should not cause problems in the wiki.\n\n";
		$delay=5;
	} else {
		print "  WARNING: This store is currently used by SMW! Deleting it\n           will cause problems in the wiki if SMW is enabled.\n\n";
		$delay=20;
	}

	print "Abort with CTRL-C in the next $delay seconds ...  ";

	for ($i = $delay+1; $i >= 1;) {
		print_c($i, --$i);
		sleep(1);
	}
	echo "\n";
	smwfGetStore()->drop($verbose);
	wfRunHooks('smwDropTables');
	print "\n";
	while (ob_get_level() > 0) { // be sure to have some buffer, otherwise some PHPs complain
		ob_end_flush();
	}
	echo "\n  All storage structures for $smwgDefaultStore have been deleted.\n  You can recreate them with this script, and then use\n  SMW_refreshData.php to rebuild their contents.";
} else {
	smwfGetStore()->setup();
	wfRunHooks('smwInitializeTables');
}

print "\n\nDone.\n";


