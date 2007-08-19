<?php
/**
 * @author Markus Krötzsch
 *
 * This page shows all used attributes.
 */

if (!defined('MEDIAWIKI')) die();

global $smwgIP;
include_once( "$smwgIP/specials/QueryPages/SMW_QueryPage.php" );

class PropertiesPage extends SMWQueryPage {

	function getName() {
		/// TODO: should probably use SMW prefix
		return "Properties";
	}

	function isExpensive() {
		return false; /// disables caching for now
	}

	function isSyndicated() { 
		return false; ///TODO: why not?
	}

	function getPageHeader() {
		return '<p>' . wfMsg('smw_properties_docu') . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		$typestring = '';
		$errors = array();
		if ($result[1]<=5) {
			$errors[] = wfMsg('smw_propertyhardlyused');
		}
		if ($result[0]->exists()) {
			$types = smwfGetStore()->getSpecialValues($result[0], SMW_SP_HAS_TYPE);
			if (count($types) >= 1) {
				$typestring = $types[0]->getLongHTMLText($skin);
			}
			$proplink = $skin->makeKnownLinkObj( $result[0], $result[0]->getText());
		} else {
			$errors[] = wfMsg('smw_propertylackspage');
			$proplink = $skin->makeBrokenLinkObj( $result[0], $result[0]->getText(), 'action=view');
		}
		if ($typestring == '') {
			$type = SMWDataValueFactory::newSpecialValue(SMW_SP_HAS_TYPE);
			$type->setXSDValue('_wpg');
			$typestring = $type->getLongHTMLText($skin);
			$errors[] = wfMsg('smw_propertylackstype', $type->getLongHTMLText($skin));
		}
		return wfMsg('smw_property_template', $proplink, $typestring, $result[1]) . ' ' . smwfEncodeMessages($errors);
	}
	
	function getResults($requestoptions) {
		return smwfGetStore()->getPropertiesSpecial($requestoptions);
	}

}

