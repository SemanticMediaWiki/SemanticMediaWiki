<?php

/**
 * This special page (Special:UnusedProperties) for MediaWiki shows all unused
 * properties.
 *
 * @file SMW_SpecialUnusedProperties.php
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
 */
class SMWUnusedPropertiesPage extends SMWQueryPage {

	function getName() {
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

	function formatResult( $skin, /* SMWDIProperty */ $result ) {
		$linker = smwfGetLinker();
		
		$proplink = $linker->link(
			$result->getDiWikiPage()->getTitle(),
			$result->getLabel()
		);

		$types = smwfGetStore()->getPropertyValues( $result->getDiWikiPage(), new SMWDIProperty( '_TYPE' ) );
		$errors = array();

		if ( count( $types ) >= 1 ) {
			$typestring = SMWDataValueFactory::newDataItemValue( current( $types ), new SMWDIProperty( '_TYPE' ) )->getLongHTMLText( $linker );
		} else {
			$type = SMWTypesValue::newFromTypeId( '_wpg' );
			$typestring = $type->getLongHTMLText( $linker );
			$errors[] = wfMsg( 'smw_propertylackstype', $type->getLongHTMLText() );
		}

		return wfMsg( 'smw_unusedproperty_template', $proplink, $typestring ) . ' ' . smwfEncodeMessages( $errors );
	}

	/**
	 * @return array of SMWDIProperty
	 */
	function getResults( $requestoptions ) {
		return smwfGetStore()->getUnusedPropertiesSpecial( $requestoptions );
	}

}

