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

/**
 * This query page shows all used properties.
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
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
		if ($result[0]->isUserDefined() && ($result[1]<=5)) {
			$errors[] = wfMsg('smw_propertyhardlyused');
		}
		if ($result[0]->isUserDefined() && $result[0]->getWikiPageValue()->getTitle()->exists()) { // FIXME: this bypasses SMWDataValueFactory; ungood
			$types = smwfGetStore()->getPropertyValues($result[0]->getWikiPageValue(), SMWPropertyValue::makeProperty('_TYPE'));
			if (count($types) >= 1) {
				$typestring = current($types)->getLongHTMLText($skin);
			}
			$proplink = $skin->makeKnownLinkObj( $result[0]->getWikiPageValue()->getTitle(), $result[0]->getWikiValue());
		} elseif ($result[0]->isUserDefined()) {
			$errors[] = wfMsg('smw_propertylackspage');
			$proplink = $skin->makeBrokenLinkObj( $result[0]->getWikiPageValue()->getTitle(), $result[0]->getWikiValue(), 'action=view');
		} else { // predefined property
			$type = $result[0]->getTypesValue();
			$typestring = $type->getLongHTMLText($skin);
			if ($typestring == '') $typestring = '&ndash;'; /// FIXME some types o fbuiltin props have no name, and another message should be used then
			$proplink = $result[0]->getLongHTMLText($skin);
		}
		if ($typestring == '') {
			$type = SMWDataValueFactory::newPropertyObjectValue(SMWPropertyValue::makeProperty('_TYPE'));
			$type->setDBkeys(array('_wpg'));
			$typestring = $type->getLongHTMLText($skin);
			if ($result[0]->getWikiPageValue()->getTitle()->exists()) { // print only when we did not print a "nopage" warning yet
				$errors[] = wfMsg('smw_propertylackstype', $type->getLongHTMLText());
			}
		}
		return wfMsg('smw_property_template', $proplink, $typestring, $result[1]) . ' ' . smwfEncodeMessages($errors);
	}

	function getResults($requestoptions) {
		return smwfGetStore()->getPropertiesSpecial($requestoptions);
	}

}

