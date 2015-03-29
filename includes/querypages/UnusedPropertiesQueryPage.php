<?php

namespace SMW;

use SMWTypesValue;
use SMWDIError;

use Html;

/**
 * Query page that provides content to Special:UnusedProperties
 *
 * @ingroup QueryPage
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class UnusedPropertiesQueryPage extends QueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getName() {
		return "UnusedProperties";
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isExpensive() {
		return false; // Disables caching for now
	}

	/**
	 * @codeCoverageIgnore
	 * @return boolean
	 */
	function isSyndicated() {
		return false; // TODO: why not?
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getPageHeader() {
		return Html::element( 'p', array(), $this->msg( 'smw_unusedproperties_docu' )->text() );
	}

	/**
	 * Format a result in the list of results as a string. We expect the
	 * result to be an object of type SMWDIProperty (normally) or maybe
	 * SMWDIError (if something went wrong).
	 *
	 * @param Skin $skin provided by MediaWiki, not needed here
	 * @param mixed $result
	 *
	 * @return String
	 * @throws InvalidResultException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {

		if ( $result instanceof DIProperty ) {
			return $this->formatPropertyItem( $result );
		} elseif ( $result instanceof SMWDIError ) {
			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( array( $result->getErrors() ) )
				->getHtml();
		} else {
			throw new InvalidResultException( 'UnusedPropertiesQueryPage expects results that are properties or errors.' );
		}
	}

	/**
	 * Produce a formatted string representation for showing a property in
	 * the list of unused properties.
	 *
	 * @since 1.8
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	protected function formatPropertyItem( DIProperty $property ) {

		// Clear formatter before invoking messages and
		// avoid having previous data to be present
		$this->getMessageFormatter()->clear();

		if ( $property->isUserDefined() ) {

			$title = $property->getDiWikiPage()->getTitle();

			if ( !$title instanceof \Title ) {
				return '';
			}

			$propertyLink = $this->getLinker()->link(
				$title,
				$property->getLabel()
			);

			$types = $this->store->getPropertyValues( $property->getDiWikiPage(), new DIProperty( '_TYPE' ) );

			if ( count( $types ) >= 1 ) {
				$typeDataValue = DataValueFactory::getInstance()->newDataItemValue( current( $types ), new DIProperty( '_TYPE' ) );
			} else {
				$typeDataValue = SMWTypesValue::newFromTypeId( '_wpg' );
				$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typeDataValue->getLongHTMLText() );
			}

		} else {
			$typeDataValue = SMWTypesValue::newFromTypeId( $property->findPropertyTypeID() );
			$propertyLink  = DataValueFactory::getInstance()->newDataItemValue( $property, null )->getShortHtmlText( $this->getLinker() );
		}

		return $this->msg( 'smw_unusedproperty_template', $propertyLink, $typeDataValue->getLongHTMLText( $this->getLinker() )	)->text() . ' ' .
			$this->getMessageFormatter()->getHtml();
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getUnusedPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}
}
