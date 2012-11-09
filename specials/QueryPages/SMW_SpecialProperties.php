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

		$wgOut->setPageTitle( wfMessage( 'properties' )->text() );

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
		return '<p>' . wfMessage( 'smw_properties_docu' )->text() . "</p><br />\n";
	}

	function getName() {
		return 'Properties';
	}

	/**
	 * Format a result in the list of results as a string. We expect the
	 * result to be an array with one object of type SMWDIProperty
	 * (normally) or maybe SMWDIError (if something went wrong), followed
	 * by a number (how often the property is used).
	 *
	 * @param Skin $skin provided by MediaWiki, not needed here
	 * @param mixed $result
	 * @return String
	 * @throws MWException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {
		list ( $dataItem, $useCount ) = $result;

		if ( $dataItem instanceof SMWDIProperty ) {
			return $this->formatPropertyItem( $dataItem, $useCount );
		} elseif ( $dataItem instanceof SMWDIError ) {
			return smwfEncodeMessages( $dataItem->getErrors() );
		} else {
			throw MWException( 'SMWUnusedPropertiesPage expects results that are properties or errors.' );
		}
	}

	/**
	 * Produce a formatted string representation for showing a property and
	 * its usage count in the list of used properties.
	 *
	 * @since 1.8
	 *
	 * @param SMWDIProperty $property
	 * @param integer $useCount
	 * @return string
	 */
	protected function formatPropertyItem( SMWDIProperty $property, $useCount ) {
		global $wgLang;
		$linker = smwfGetLinker();

		$errors = array();

		$diWikiPage = $property->getDiWikiPage();
		$title = !is_null( $diWikiPage ) ? $diWikiPage->getTitle() : null;

		if ( $property->isUserDefined() && is_null( $title ) ) {
			// Show even messed up property names.
			$typestring = '';
			$proplink = $property->getLabel();
			$errors[] = wfMessage( 'smw_notitle', $property->getLabel() )->escaped();
		} elseif ( $property->isUserDefined() ) {

			if ( $useCount <= 5 ) {
				$errors[] = wfMessage( 'smw_propertyhardlyused' )->escaped();
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
					$errors[] = wfMessage( 'smw_propertylackstype' )->rawParams( $typestring )->escaped();
				}

				$proplink = $linker->link( $title, $label );
			} else {
				$errors[] = wfMessage( 'smw_propertylackspage' )->escaped();
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
			// @todo Should use numParams for $useCount?
			return wfMessage( 'smw_property_template_notype' )
				->rawParams( $proplink )->params( $useCount )->text() . ' ' . $warnings;
		} else {
			// @todo Should use numParams for $useCount?
			return wfMessage( 'smw_property_template' )
				->rawParams( $proplink, $typestring )
				->params( $useCount )->escaped() . ' ' . $warnings;
		}
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of array( SMWDIProperty|SMWDIError, integer )
	 */
	function getResults( $requestoptions ) {
		return smwfGetStore()->getPropertiesSpecial( $requestoptions );
	}
}
