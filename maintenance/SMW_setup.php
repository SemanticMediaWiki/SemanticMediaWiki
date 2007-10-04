<?php
/**
 * Sets up the storage backend currently selected in LocalSettings.php
 * (or the default MySQL store if no other store was selected). This
 * is equivalent to clicking the respective button on the page
 * Special:SMWAdmin. However, the latter may timeout if the setup involves
 * migrating a lot of existing data.
 *
 * Note: this file must be placed in MediaWiki's "maintenance" directory!
 *
 * Usage:
 * php SMW_refreshData.php [options...]
 *
 * @author Markus Krötzsch
 */

require_once( 'commandLine.inc' );

global $smwgIP;
require_once($smwgIP . '/includes/SMW_GlobalFunctions.php');

smwfGetStore()->setup();
wfRunHooks('smwInitializeTables');

print "\n\nDone.\n";


