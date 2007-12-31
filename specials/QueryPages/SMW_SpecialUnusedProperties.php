<?php
/**
 * @author Markus Krötzsch
 *
 * This page shows all used attributes.
 */

if (!defined('MEDIAWIKI')) die();

global $smwgIP;
include_once( "$smwgIP/specials/QueryPages/SMW_QueryPage.php" );

function smwfDoSpecialUnusedProperties() {
	wfProfileIn('smwfDoSpecialUnusedProperties (SMW)');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new SMWUnusedPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	wfProfileOut('smwfDoSpecialUnusedProperties (SMW)');
	return $result;
}

class SMWUnusedPropertiesPage extends SMWQueryPage {

	function getName() {
		/// TODO: should probably use SMW prefix
		return "UnusedProperties";
	}

	function isExpensive() {
		return false; /// disables caching for now
	}

	function isSyndicated() { 
		return false; ///TODO: why not?
	}

	function getPageHeader() {
		return '<p>' . wfMsg('smw_unusedproperties_docu') . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		$proplink = $skin->makeKnownLinkObj( $result, $result->getText() );
		$types = smwfGetStore()->getSpecialValues($result, SMW_SP_HAS_TYPE);
		$errors = array();
		if (count($types) >= 1) {
			$typestring = $types[0]->getLongHTMLText($skin);
		} else {
			$type = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE);
			$type->setXSDValue('_wpg');
			$typestring = $type->getLongHTMLText($skin);
			$errors[] = wfMsg('smw_propertylackstype', $type->getLongHTMLText());
		}
		return wfMsg('smw_unusedproperty_template', $proplink, $typestring) . ' ' . smwfEncodeMessages($errors);
	}
	
	function getResults($requestoptions) {
		return smwfGetStore()->getUnusedPropertiesSpecial($requestoptions);
	}

}

