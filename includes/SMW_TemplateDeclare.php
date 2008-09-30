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

function smwfTemplateDeclare_Render( Parser &$parser, PPFrame $frame, $args ) {
	if ($frame->isTemplate()) {

		foreach ($args as $arg)
			if (trim($arg) != "") {
				$expanded = trim( $frame->expand( $arg ));
				$parts = explode("=", $expanded, 2);
				if (count($parts)==1) {
					$propertystring = $expanded;
					$argumentname = $expanded;
				} else {
					$propertystring = $parts[0];
					$argumentname = $parts[1];
				}
				$property = Title::newFromText( $propertystring, SMW_NS_PROPERTY );
				//if ($property == null) continue;
				$argument = $frame->getArgument($argumentname);
				$valuestring = $frame->expand($argument);
				if ($property != null) {
					$type = SMWDataValueFactory::getPropertyObjectTypeID($property);
					if ($type == "_wpg") {
						$matches = array();
						preg_match_all("/\[\[([^\[\]]*)\]\]/", $valuestring, $matches);
						$objects = $matches[1];
						if (count($objects) == 0) {
							if (trim($valuestring) != '') {
								SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
							}
						} else {
							foreach ($objects as $object) {
								SMWParseData::addProperty( $propertystring, $object, false, $parser, true );
							}
						}
					} else {
						if (trim($valuestring) != '') {
							SMWParseData::addProperty( $propertystring, $valuestring, false, $parser, true );
						}
					}
					$value = SMWDataValueFactory::newPropertyObjectValue($property, $valuestring);
					//if (!$value->isValid()) continue;
				}
			}

	} else {
	
		// @todo Save as metadata

	}
	
	return;
}
