<?php
/**
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * This page shows all used properties.
 */

function smwfDoSpecialProperties() {
	global $wgOut;
	wfProfileIn('smwfDoSpecialProperties (SMW)');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new SMWPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	SMWOutputs::commitToOutputPage($wgOut); // make sure locally collected output data is pushed to the output!
	wfProfileOut('smwfDoSpecialProperties (SMW)');
	return $result;
}

class SMWPropertiesPage extends SMWQueryPage {

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
		wfLoadExtensionMessages('SemanticMediaWiki');
		return '<p>' . wfMsg('smw_properties_docu') . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		$typestring = '';
		$errors = array();
		wfLoadExtensionMessages('SemanticMediaWiki');
		if ($result[1]<=5) {
			$errors[] = wfMsg('smw_propertyhardlyused');
		}
		if ($result[0]->exists()) {
			$types = smwfGetStore()->getSpecialValues($result[0], SMW_SP_HAS_TYPE);
			if (count($types) >= 1) {
				$typestring = current($types)->getLongHTMLText($skin);
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
			if ($result[0]->exists()) { // print only when we did not print a "nopage" warning yet
				$errors[] = wfMsg('smw_propertylackstype', $type->getLongHTMLText());
			}
		}
		return wfMsg('smw_property_template', $proplink, $typestring, $result[1]) . ' ' . smwfEncodeMessages($errors);
	}
	
	function getResults($requestoptions) {
		return smwfGetStore()->getPropertiesSpecial($requestoptions);
	}

}

