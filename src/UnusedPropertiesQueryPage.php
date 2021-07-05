<?php

namespace SMW;

use Html;
use SMW\Exception\PropertyNotFoundException;
use SMWDIError;
use SMWTypesValue;

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
	 * Returns available cache information (takes into account user preferences)
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheInfo() {

		if ( $this->listLookup->isFromCache() ) {
			return $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() ) )->parse();
		}

		return '';
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getPageHeader() {

		return Html::rawElement(
			'p',
			[ 'class' => 'smw-unusedproperties-docu' ],
			$this->msg( 'smw-unusedproperties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property' ), $this->getCacheInfo() ) .
		Html::element(
			'h2',
			[],
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
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
				->addFromArray( [ $result->getErrors() ] )
				->getHtml();
		}

		throw new PropertyNotFoundException( 'UnusedPropertiesQueryPage expects results that are properties or errors.' );
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

			if ( is_array( $types ) && count( $types ) >= 1 ) {
				$typeDataValue = DataValueFactory::getInstance()->newDataValueByItem( current( $types ), new DIProperty( '_TYPE' ) );
			} else {
				$typeDataValue = SMWTypesValue::newFromTypeId( '_wpg' );
				$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typeDataValue->getLongHTMLText() );
			}

		} else {
			$typeDataValue = SMWTypesValue::newFromTypeId( $property->findPropertyTypeID() );
			$propertyLink  = DataValueFactory::getInstance()->newDataValueByItem( $property, null )->getShortHtmlText( $this->getLinker() );
		}

		return $this->msg( 'smw-unusedproperty-template', $propertyLink, $typeDataValue->getLongHTMLText( $this->getLinker() )	)->text() . ' ' .
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
