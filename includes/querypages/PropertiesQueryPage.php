<?php

namespace SMW;

use SMWTypesValue;
use SMWDIError;

use Html;

/**
 * Query class that provides content for the Special:Properties page
 *
 * @ingroup QueryPage
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class PropertiesQueryPage extends QueryPage {

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
	 * Returns available cache information (takes into account user preferences)
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheInfo() {

		if ( $this->listLookup->isCached() ) {
			return $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() ) )->parse();
		}

		return '';
	}

	/**
	 * @return string
	 */
	function getPageHeader() {
		return Html::rawElement(
			'p',
			array( 'class' => 'smw-sp-properties-docu' ),
			$this->msg( 'smw-sp-properties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property' ), $this->getCacheInfo() ) .
		Html::element(
			'h2',
			array(),
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
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
	 * @throws InvalidResultException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {

		list ( $dataItem, $useCount ) = $result;

		if ( $dataItem instanceof DIProperty ) {
			return $this->formatPropertyItem( $dataItem, $useCount );
		} elseif ( $dataItem instanceof SMWDIError ) {
			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( array( $dataItem->getErrors() ) )
				->getHtml();
		} else {
			throw new InvalidResultException( 'PropertiesQueryPage expects results that are properties or errors.' );
		}
	}

	/**
	 * Produce a formatted string representation for showing a property and
	 * its usage count in the list of used properties.
	 *
	 * @since 1.8
	 *
	 * @param DIProperty $property
	 * @param integer $useCount
	 * @return string
	 */
	protected function formatPropertyItem( DIProperty $property, $useCount ) {

		// Clear formatter before invoking messages
		$this->getMessageFormatter()->clear();

		$diWikiPage = $property->getDiWikiPage();
		$title = $diWikiPage !== null ? $diWikiPage->getTitle() : null;

		if ( $useCount == 0 && !$this->settings->get( 'smwgPropertyZeroCountDisplay' ) ) {
			return '';
		}

		if ( $property->isUserDefined() ) {

			if (  $title === null ) {
				// Show even messed up property names.
				$typestring = '';
				$proplink = $property->getLabel();
				$this->getMessageFormatter()->addFromKey( 'smw_notitle', $proplink );
			} else {
				list( $typestring, $proplink ) = $this->getUserDefinedPropertyInfo( $title, $property, $useCount );
			}

		} else {
			list( $typestring, $proplink ) = $this->getPredefinedPropertyInfo( $property );
		}

		if ( $typestring === '' ) { // Built-ins have no type

			// @todo Should use numParams for $useCount?
			return $this->msg( 'smw_property_template_notype' )
				->rawParams( $proplink )->numParams( $useCount )->text() . ' ' .
				$this->getMessageFormatter()->setType( 'warning' )->escape( false )->getHtml();

		} else {

			// @todo Should use numParams for $useCount?
			return $this->msg( 'smw_property_template' )
				->rawParams( $proplink, $typestring )->numParams( $useCount )->escaped() . ' ' .
				$this->getMessageFormatter()->setType( 'warning' )->escape( false )->getHtml();

		}
	}

	/**
	 * Returns information related to user-defined properties
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param DIProperty $property
	 * @param integer $useCount
	 *
	 * @return array
	 */
	private function getUserDefinedPropertyInfo( $title, $property, $useCount ) {

		if ( $useCount <= $this->settings->get( 'smwgPropertyLowUsageThreshold' ) ) {
			$this->getMessageFormatter()->addFromKey( 'smw_propertyhardlyused' );
		}

		// User defined types default to Page
		$typestring = SMWTypesValue::newFromTypeId( $this->settings->get( 'smwgPDefaultType' ) )->getLongHTMLText( $this->getLinker() );

		$label = htmlspecialchars( $property->getLabel() );

		if ( $title->exists() ) {

			$typeProperty = new DIProperty( '_TYPE' );
			$types = $this->store->getPropertyValues( $property->getDiWikiPage(), $typeProperty );

			if ( count( $types ) >= 1 ) {

				$typeDataValue = DataValueFactory::getInstance()->newDataItemValue( current( $types ), $typeProperty );
				$typestring = $typeDataValue->getLongHTMLText( $this->getLinker() );

			} else {

				$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typestring );
			}

			$proplink = $this->getLinker()->link( $title, $label );

		} else {

			$this->getMessageFormatter()->addFromKey( 'smw_propertylackspage' );
			$proplink = $this->getLinker()->link( $title, $label, array(), array( 'action' => 'view' ) );
		}

		return array( $typestring, $proplink );
	}

	/**
	 * Returns information related to predefined properties
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	private function getPredefinedPropertyInfo( DIProperty $property ) {
		return array(
			SMWTypesValue::newFromTypeId( $property->findPropertyTypeID() )->getLongHTMLText( $this->getLinker() ),
			DataValueFactory::getInstance()->newDataItemValue( $property, null )->getShortHtmlText( $this->getLinker() )
		);
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of array( SMWDIProperty|SMWDIError, integer )
	 */
	function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}

}
