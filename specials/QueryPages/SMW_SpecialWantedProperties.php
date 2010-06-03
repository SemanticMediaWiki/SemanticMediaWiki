<?php

/**
 * File holding the SMWSpecialWantedProperties class for the Special:WantedProperties page. 
 *
 * @file SMW_SpecialWantedProperties.php
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

/**
 * This special page for MediaWiki shows all wanted properties (used but not having a page).
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWSpecialWantedProperties extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'WantedProperties' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}
	
	public function execute( $param ) {
		wfProfileIn( 'smwfDoSpecialWantedProperties (SMW)' );
		
		global $wgOut;
		
		$wgOut->setPageTitle( wfMsg( 'wantedproperties' ) );
		
		$rep = new SMWWantedPropertiesPage();
		
		list( $limit, $offset ) = wfCheckLimits();
		$rep->doQuery( $offset, $limit );
		
		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $wgOut );
		
		wfProfileOut( 'smwfDoSpecialWantedProperties (SMW)' );	
	}
}

/**
 * This query page shows all wanted properties.
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
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
		return '<p>' . wfMsg( 'smw_wantedproperties_docu' ) . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang;
		if ( $result[0]->isUserDefined() ) {
			$proplink = $skin->makeLinkObj( $result[0]->getWikiPageValue()->getTitle(), $result[0]->getWikiValue(), 'action=view' );
		} else {
			$proplink = $result[0]->getLongHTMLText( $skin );
		}
		return wfMsgExt( 'smw_wantedproperty_template', array( 'parsemag' ), $proplink, $result[1] );
	}

	function getResults( $requestoptions ) {
		return smwfGetStore()->getWantedPropertiesSpecial( $requestoptions );
	}
}
