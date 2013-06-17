<?php

define( 'MEDIAWIKI', true );

global $IP;
$IP = getenv( 'MW_INSTALL_PATH' );

if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../../..';
}

$self = 'foobar';

// Detect compiled mode
# Get the MWInit class
require_once "$IP/includes/Init.php";
require_once "$IP/includes/AutoLoader.php";
# Stub the profiler
require_once "$IP/includes/profiler/Profiler.php";

# Start the profiler
$wgProfiler = array();
if ( file_exists( "$IP/StartProfiler.php" ) ) {
	require "$IP/StartProfiler.php";
}

// Some other requires
require_once "$IP/includes/Defines.php";

require_once MWInit::compiledPath( 'includes/DefaultSettings.php' );

foreach ( get_defined_vars() as $key => $var ) {
	if ( !array_key_exists( $key, $GLOBALS ) ) {
		$GLOBALS[$key] = $var;
	}
}

if ( defined( 'MW_CONFIG_CALLBACK' ) ) {
	# Use a callback function to configure MediaWiki
	MWFunction::call( MW_CONFIG_CALLBACK );
} else {
	// Require the configuration (probably LocalSettings.php)
	require loadSettings();
}

// Some last includes
require_once MWInit::compiledPath( 'includes/Setup.php' );

// Much much faster startup than creating a title object
$wgTitle = null;


function loadSettings() {
	global $wgCommandLineMode, $IP;

	$settingsFile = "$IP/LocalSettings.php";

	if ( !is_readable( $settingsFile ) ) {
		$this->error( "A copy of your installation's LocalSettings.php\n" .
			"must exist and be readable in the source directory.\n" .
			"Use --conf to specify it.", true );
	}
	$wgCommandLineMode = true;
	return $settingsFile;
}