<?php

/**
 * File holding the SMWSpecialUnusedProperties class for the Special:UnusedProperties page. 
 *
 * @file SMW_SpecialUnusedProperties.php
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
 * This special page for MediaWiki shows all unused properties.
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWSpecialUnusedProperties extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'UnusedProperties' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	public function execute( $param ) {
		wfProfileIn( 'smwfDoSpecialUnusedProperties (SMW)' );
			
		global $wgOut;
		
		$wgOut->setPageTitle( wfMsg( 'unusedproperties' ) );
		
		$rep = new SMWUnusedPropertiesPage();
		
		list( $limit, $offset ) = wfCheckLimits();
		$rep->doQuery( $offset, $limit );
		
		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $wgOut );
		
		wfProfileOut( 'smwfDoSpecialUnusedProperties (SMW)' );
	}
}

/**
 * This query page shows all unused properties.
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
 * 
 * TODO: A delete button that removes all non-used property pages would be quite usefull.
 */
class SMWUnusedPropertiesPage extends SMWQueryPage {

	function getName() {
		// TODO: should probably use SMW prefix
		return "UnusedProperties";
	}

	function isExpensive() {
		return false; // Disables caching for now
	}

	function isSyndicated() {
		return false; // TODO: why not?
	}

	function getPageHeader() {
		return '<p>' . wfMsg( 'smw_unusedproperties_docu' ) . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		global $wgLang;
		$proplink = $skin->makeKnownLinkObj( $result->getWikiPageValue()->getTitle(), $result->getWikiValue() );
		$types = smwfGetStore()->getPropertyValues( $result->getWikiPageValue(), SMWPropertyValue::makeProperty( '_TYPE' ) ); // TODO: do not bypass SMWDataValueFactory!
		$errors = array();
		if ( count( $types ) >= 1 ) {
			$typestring = current( $types )->getLongHTMLText( $skin );
		} else {
			$type = SMWDataValueFactory::newPropertyObjectValue( SMWPropertyValue::makeProperty( '_TYPE' ) );
			$type->setDBkeys( array( '_wpg' ) );
			$typestring = $type->getLongHTMLText( $skin );
			$errors[] = wfMsg( 'smw_propertylackstype', $type->getLongHTMLText() );
		}
		return wfMsg( 'smw_unusedproperty_template', $proplink, $typestring ) . ' ' . smwfEncodeMessages( $errors );
	}

	function getResults( $requestoptions ) {
		return smwfGetStore()->getUnusedPropertiesSpecial( $requestoptions );
	}

}

