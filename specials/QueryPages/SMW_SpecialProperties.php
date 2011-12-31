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
		global $wgLang;
		$linker = smwfGetLinker();
		list ( $property, $useCount ) = $result;
		
		$errors = array();

		$diWikiPage = $property->getDiWikiPage();
		$title = !is_null( $diWikiPage ) ? $diWikiPage->getTitle() : null;

		if ( $property->isUserDefined() ) {

			if ( $title === null ) {
				return '';
			}

			if ( $useCount <= 5 ) {
				$errors[] = wfMsgHtml( 'smw_propertyhardlyused' );
			}

			// User defined types default to Page
			global $smwgPDefaultType;
			$typeDataValue = SMWTypesValue::newFromTypeId( $smwgPDefaultType );
			$typestring = $typeDataValue->getLongHTMLText( $linker );

			$label = htmlspecialchars( $property->getLabel() );
			if ( $title->exists() ) {
				$typeProperty = new SMWDIProperty( '_TYPE' );
				$types = smwfGetStore()->getPropertyValues( $diWikiPage, $typeProperty );
				if ( count( $types ) >= 1 ) {
					$typeDataValue = SMWDataValueFactory::newDataItemValue( current( $types ), $typeProperty );
					$typestring = $typeDataValue->getLongHTMLText( $linker );
				} else {
					$errors[] = wfMsgHtml( 'smw_propertylackstype', $typestring );
				}

				$proplink = $linker->link( $title, $label );
			} else {
				$errors[] = wfMsgHtml( 'smw_propertylackspage' );
				$proplink = $linker->link( $title, $label, array(), array( 'action' => 'view' ) );
			}

		} else { // predefined property
			$typeid = $property->findPropertyTypeID();
			$typeDataValue = SMWTypesValue::newFromTypeId( $typeid );
			$typestring = $typeDataValue->getLongHTMLText( $linker );
			$propertyDataValue = SMWDataValueFactory::newDataItemValue( $property, null );
			$proplink = $propertyDataValue->getShortHtmlText( $linker );
		}

		$warnings = smwfEncodeMessages( $errors, 'warning', '', false );

		$useCount = $wgLang->formatNum( $useCount );
		if ( $typestring === '' ) { // Builtins have no type
			return wfMsgHtml( 'smw_property_template_notype', $proplink, $useCount ) . ' ' . $warnings;
		} else {
			return wfMsgHtml( 'smw_property_template', $proplink, $typestring, $useCount ) . ' ' . $warnings;
		}
	}

	function getResults( $requestoptions ) {
		return smwfGetStore()->getPropertiesSpecial( $requestoptions );
	}

}
