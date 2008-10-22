<?php
/**
 * @author Markus Krötzsch
 *
 * This page shows all wanted properties (used but not having a page).
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

function smwfDoSpecialWantedProperties() {
	global $wgOut;
	wfProfileIn('smwfDoSpecialWantedProperties (SMW)');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new SMWWantedPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	SMWOutputs::commitToOutputPage($wgOut); // make sure locally collected output data is pushed to the output!
	wfProfileOut('smwfDoSpecialWantedProperties (SMW)');
	return $result;
}

/**
 * @ingroup SMWQuery
 */
class SMWWantedPropertiesPage extends SMWQueryPage {

	function getName() {
		/// TODO: should probably use SMW prefix
		return "WantedProperties";
	}

	function isExpensive() {
		return false; /// disables caching for now
	}

	function isSyndicated() { 
		return false; ///TODO: why not?
	}

	function getPageHeader() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return '<p>' . wfMsg('smw_wantedproperties_docu') . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		if ($result[0]->isUserDefined()) {
			$proplink = $skin->makeLinkObj($result[0]->getWikiPageValue()->getTitle(), $result[0]->getWikiValue(), 'action=view');
		} else {
			$proplink = $result[0]->getLongHTMLText($skin);
		}
		wfLoadExtensionMessages('SemanticMediaWiki');
		return wfMsg('smw_wantedproperty_template', $proplink, $result[1]);
	}
	
	function getResults($requestoptions) {
		return smwfGetStore()->getWantedPropertiesSpecial($requestoptions);
	}

}

