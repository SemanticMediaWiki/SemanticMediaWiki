<?php
/**
 * @author Markus Krötzsch
 *
 * This page shows all unused properties.
 *
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */


function smwfDoSpecialUnusedProperties() {
	global $wgOut;
	wfProfileIn('smwfDoSpecialUnusedProperties (SMW)');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new SMWUnusedPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	SMWOutputs::commitToOutputPage($wgOut); // make sure locally collected output data is pushed to the output!
	wfProfileOut('smwfDoSpecialUnusedProperties (SMW)');
	return $result;
}

/**
 * @ingroup SMW
 */
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
		wfLoadExtensionMessages('SemanticMediaWiki');
		return '<p>' . wfMsg('smw_unusedproperties_docu') . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		$proplink = $skin->makeKnownLinkObj( $result, $result->getText() );
		$types = smwfGetStore()->getSpecialValues($result, SMW_SP_HAS_TYPE);
		$errors = array();
		wfLoadExtensionMessages('SemanticMediaWiki');
		if (count($types) >= 1) {
			$typestring = current($types)->getLongHTMLText($skin);
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

