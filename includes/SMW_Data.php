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

/**
 * Parser function for data, that enables to use a parser function for annotations.
 * Using the following syntax:
 * {{#set:
 *   population = 13000
 * | area = 396 km²
 * | sea = Adria
 * }}
 * This creates annotations with the properties as stated on the left side, and the
 * values on the right side.
 * 
 * @param[in] &$parser Parser  The current parser
 * @return nothing
 */
function smwfDataEntry_Render( &$parser ) {
	$params = func_get_args();
	array_shift( $params ); // we already know the $parser ...
	foreach ($params as $p)
		if (trim($p) != "") {
			$parts = explode("=", trim($p));
			if (count($parts)==2) {
				$property = $parts[0];
				$subject = $parts[1];
				// Adds the fact to the factbox, which may be problematic in case
				// the parser gets called several times...
				SMWParseData::addProperty( $property, $subject, false, $parser, true );
			}
		}
	return;
}
