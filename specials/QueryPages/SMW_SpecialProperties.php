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

	function getName() {
		// TODO: should probably use SMW prefix
		return "Properties";
	}

	function isExpensive() {
		return false; // Disables caching for now
	}

	function isSyndicated() {
		return false; // TODO: why not?
	}

	function getPageHeader() {
		return '<p>' . wfMsg( 'smw_properties_docu' ) . "</p><br />\n";
	}

	function formatResult( $skin, $result ) {
		$typestring = '';
		$errors = array();
		$diWikiPage = $result[0]->getDiWikiPage();
		if ( $diWikiPage !== null ) {
			$title = Title::makeTitle( $diWikiPage->getNamespace(), $diWikiPage->getDBkey() );
		} else {
			$title = null;
		}
		if ( $result[0]->isUserDefined() && ( $result[1] <= 5 ) ) {
			$errors[] = wfMsg( 'smw_propertyhardlyused' );
		}
		if ( $result[0]->isUserDefined() && ( $title !== null ) && $title->exists() ) { // FIXME: this bypasses SMWDataValueFactory; ungood
			$typeProperty = new SMWDIProperty( '_TYPE' );
			$types = smwfGetStore()->getPropertyValues( $diWikiPage, $typeProperty );
			if ( count( $types ) >= 1 ) {
				$typeDataValue = SMWDataValueFactory::newDataItemValue( current( $types ), $typeProperty );
				$typestring = $typeDataValue->getLongHTMLText( $skin );
			}
			$proplink = $skin->makeKnownLinkObj( $title, $result[0]->getLabel() );
		} elseif ( $result[0]->isUserDefined() && ( $title !== null ) ) {
			$errors[] = wfMsg( 'smw_propertylackspage' );
			$proplink = $skin->makeBrokenLinkObj( $title, $result[0]->getLabel(), 'action=view' );
		} else { // predefined property
			$type = $result[0]->getTypesValue();
			$typestring = $type->getLongHTMLText( $skin );
			if ( $typestring == '' ) $typestring = '–'; /// FIXME some types of builtin props have no name, and another message should be used then
			$proplink = $result[0]->getLongHTMLText( $skin );
		}
		if ( $typestring == '' ) {
			global $smwgPDefaultType;
			$typepagedbkey = str_replace( ' ', '_', SMWDataValueFactory::findTypeLabel( $smwgPDefaultType ) );
			$diTypePage = new SMWDIWikiPage( $typepagedbkey, SMW_NS_TYPE, '', '__typ' );
			$dvTypePage = SMWDataValueFactory::newTypeIdValue( '__typ' );
			$dvTypePage->setDataItem( $diTypePage );
			$typestring = $dvTypePage->getLongHTMLText( $skin );
			if ( ( $title !== null ) && ( $title->exists() ) ) { // print only when we did not print a "nopage" warning yet
				$errors[] = wfMsg( 'smw_propertylackstype', $typestring );
			}
		}
		return wfMsg( 'smw_property_template', $proplink, $typestring, $result[1] ) . ' ' . smwfEncodeMessages( $errors );
	}

	function getResults( $requestoptions ) {
		return smwfGetStore()->getPropertiesSpecial( $requestoptions );
	}

}
