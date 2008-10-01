<?php
/**
 * Introducing a #declare parser function in order to declare the arguments
 * of a template call, i.e. on a template page we say
 * {{#declare:Author=Author#list|Publisher=editor}}
 * and then, when the template is included in a page, the values set for the
 * fields are taken as annotations.
 * @file
 * @ingroup SMW
 * @author Denny Vrandecic
 */

global $wgExtensionFunctions, $wgHooks;
$wgExtensionFunctions[] = 'smwfTemplateDeclare_Setup';
$wgHooks['LanguageGetMagic'][] = 'smwfTemplateDeclare_Magic';

/**
 * Sets up the declare parser function.
 */
function smwfTemplateDeclare_Setup() {
	global $wgParser;
	// Set a function hook associating "declare" magic word with our function
	$wgParser->setFunctionHook( 'declare', 'smwfTemplateDeclare_Render', SFH_OBJECT_ARGS );
}

/**
 * Adds the declare magic word.
 */
function smwfTemplateDeclare_Magic( &$magicWords, $langCode ) {
	// Add the magic word
	// The first array element is case sensitive, in this case it is not case sensitive
	// All remaining elements are synonyms for our parser function
	$magicWords['declare'] = array( 0, 'declare' );
	// unless we return true, other parser functions extensions won't get loaded.
	return true;
}


