<?php

/**
 * This special page for MediaWiki shows all used properties.
 * 
 * @file SMW_SpecialProperties.php
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class SMWSpecialProperties extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'Properties' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	public function execute( $param ) {	
		wfProfileIn( 'smwfDoSpecialProperties (SMW)' );
		
		global $wgOut;
		
		$wgOut->setPageTitle( wfMsg( 'properties' ) );
		
		$rep = new SMWPropertiesPage();
		
		list( $limit, $offset ) = wfCheckLimits();
		$rep->doQuery( $offset, $limit );
		
		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $wgOut );
		
		wfProfileOut( 'smwfDoSpecialProperties (SMW)' );
	}
}

/**
 * This query page shows all used properties.
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @author Markus Krötzsch
 */
class SMWPropertiesPage extends SMWQueryPage {

	function getPageHeader() {
		return '<p>' . wfMsg( 'smw_properties_docu' ) . "</p><br />\n";
	}

	function getName() {
		return 'Properties';
	}

	function formatResult( $skin, $result ) {
		$linker = smwfGetLinker();
		
		$typestring = '';
		$errors = array();

		$diWikiPage = $result[0]->getDiWikiPage();
		$title = $diWikiPage !== null ? $diWikiPage->getTitle() : null;

		if ( $result[0]->isUserDefined() && ( $result[1] <= 5 ) ) {
			$errors[] = wfMsg( 'smw_propertyhardlyused' );
		}

		if ( $result[0]->isUserDefined() && $title !== null && $title->exists() ) {
			$typeProperty = new SMWDIProperty( '_TYPE' );
			$types = smwfGetStore()->getPropertyValues( $diWikiPage, $typeProperty );
			if ( count( $types ) >= 1 ) {
				$typeDataValue = SMWDataValueFactory::newDataItemValue( current( $types ), $typeProperty );
				$typestring = $typeDataValue->getLongHTMLText( $linker );
			}
			$proplink = $linker->makeKnownLinkObj( $title, $result[0]->getLabel() );
		} elseif ( $result[0]->isUserDefined() && $title !== null ) {
			$errors[] = wfMsg( 'smw_propertylackspage' );
			$proplink = $linker->makeBrokenLinkObj( $title, $result[0]->getLabel(), 'action=view' );
		} else { // predefined property
			$typeid = $result[0]->findPropertyTypeID();
			$typeDataValue = SMWTypesValue::newFromTypeId( $typeid );
			$propertyDataValue = SMWDataValueFactory::newDataItemValue( $result[0], null );
			$typestring = $typeDataValue->getLongHTMLText( $linker );
			if ( $typestring == '' ) $typestring = '–'; /// FIXME some types of builtin props have no name, and another message should be used then
			$proplink = $propertyDataValue->getLongHTMLText( $linker );
		}

		if ( $typestring == '' ) {
			global $smwgPDefaultType;
			$typeDataValue = SMWTypesValue::newFromTypeId( $smwgPDefaultType );
			$typestring = $typeDataValue->getLongHTMLText( $linker );
			if ( $title !== null && $title->exists() ) { // print only when we did not print a "nopage" warning yet
				$errors[] = wfMsg( 'smw_propertylackstype', $typestring );
			}
		}

		return wfMsg( 'smw_property_template', $proplink, $typestring, $result[1] ) . ' ' . smwfEncodeMessages( $errors, 'warning', ' <!--br-->', false );
	}

	function getResults( $requestoptions ) {
		return smwfGetStore()->getPropertiesSpecial( $requestoptions );
	}

}
