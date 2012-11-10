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

		$wgOut->setPageTitle( wfMessage( 'unusedproperties' )->text() );

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
		return '<p>' . wfMessage( 'smw_unusedproperties_docu' )->text() . "</p><br />\n";
	}

	/**
	 * Format a result in the list of results as a string. We expect the
	 * result to be an object of type SMWDIProperty (normally) or maybe
	 * SMWDIError (if something went wrong).
	 *
	 * @param Skin $skin provided by MediaWiki, not needed here
	 * @param mixed $result
	 * @return String
	 * @throws MWException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {
		if ( $result instanceof SMWDIProperty ) {
			return $this->formatPropertyItem( $result );
		} elseif ( $result instanceof SMWDIError ) {
			return smwfEncodeMessages( $result->getErrors() );
		} else {
			throw MWException( 'SMWUnusedPropertiesPage expects results that are properties or errors.' );
		}
	}

	/**
	 * Produce a formatted string representation for showing a property in
	 * the list of unused properties.
	 *
	 * @since 1.8
	 *
	 * @param SMWDIProperty $property
	 * @return string
	 */
	protected function formatPropertyItem( SMWDIProperty $property ) {
		$linker = smwfGetLinker();
		$errors = array();

		if ( $property->isUserDefined() ) {
			$proplink = $linker->link(
				$property->getDiWikiPage()->getTitle(),
				$property->getLabel()
			);

			$types = smwfGetStore()->getPropertyValues( $property->getDiWikiPage(), new SMWDIProperty( '_TYPE' ) );

			if ( count( $types ) >= 1 ) {
				$typeDataValue = SMWDataValueFactory::newDataItemValue( current( $types ), new SMWDIProperty( '_TYPE' ) );
			} else {
				$typeDataValue = SMWTypesValue::newFromTypeId( '_wpg' );
				$errors[] = wfMessage( 'smw_propertylackstype', $typeDataValue->getLongHTMLText() )->text();
			}

			$typeString = $typeDataValue->getLongHTMLText( $linker );
		} else {
			$typeid = $property->findPropertyTypeID();
			$typeDataValue = SMWTypesValue::newFromTypeId( $typeid );
			$typeString = $typeDataValue->getLongHTMLText( $linker );
			$propertyDataValue = SMWDataValueFactory::newDataItemValue( $property, null );
			$proplink = $propertyDataValue->getShortHtmlText( $linker );
		}

		return wfMessage( 'smw_unusedproperty_template', $proplink, $typeString )->text() . ' ' . smwfEncodeMessages( $errors );
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestOptions ) {
		return smwfGetStore()->getUnusedPropertiesSpecial( $requestOptions );
	}
}
