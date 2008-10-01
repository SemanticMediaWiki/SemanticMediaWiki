<?php
/**
 * Introducing a #set parser function as an alternative to
 * annotationg with the [[p::o]] syntax.
 * @file
 * @ingroup SMW
 * @author Denny Vrandecic 
 */

global $wgExtensionFunctions, $wgHooks;
$wgExtensionFunctions[] = 'smwfDataEntry_Setup';
$wgHooks['LanguageGetMagic'][] = 'smwfDataEntry_Magic';

/**
 * Hooks the data parser function.
 */
function smwfDataEntry_Setup() {
	global $wgParser;
	// Set a function hook associating "set" magic word with our function
	$wgParser->setFunctionHook( 'set', 'smwfDataEntry_Render' );
}

/**
 * Add the magic word for the dataparser function. 
 */
function smwfDataEntry_Magic( &$magicWords, $langCode ) {
	// Add the magic word
	// The first array element is case sensitive, in this case it is not case sensitive
	// All remaining elements are synonyms for our parser function
	$magicWords['set'] = array( 0, 'set' );
	// unless we return true, other parser functions extensions won't get loaded.
	return true;
}


