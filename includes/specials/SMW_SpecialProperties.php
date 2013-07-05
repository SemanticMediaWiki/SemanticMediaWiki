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
 * @author mwjames
 */
class SMWSpecialProperties extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Properties' );
	}

	public function execute( $param ) {
		\SMW\Profiler::In( __METHOD__ );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'properties' )->text() );

		$page = new SMWPropertiesPage(
			\SMW\StoreFactory::getStore(),
			\SMW\Settings::newFromGlobals()
		);
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = wfCheckLimits();
		$page->doQuery( $offset, $limit, $this->getRequest()->getVal( 'property' ) );

		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );

		\SMW\Profiler::Out( __METHOD__ );
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

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/** @var Collector */
	protected $collector;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( \SMW\Store $store, \SMW\Settings $settings ) {
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
		return $this->collector->isCached() ? $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->collector->getCacheDate(), $this->getUser() ) )->parse() : '';
	}

	/**
	 * @return string
	 */
	function getPageHeader() {
		return Html::rawElement(
			'p',
			array( 'class' => 'smw-sp-properties-docu' ),
			$this->msg( 'smw-sp-properties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property' ) ) .
		Html::element(
			'h2',
			array(),
			$this->msg( 'smw-sp-properties-header-label' )->text()
		) . $this->getCacheInfo();
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

		if ( $dataItem instanceof SMWDIProperty ) {
			return $this->formatPropertyItem( $dataItem, $useCount );
		} elseif ( $dataItem instanceof SMWDIError ) {

			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( array( $dataItem->getErrors() ) )
				->getHtml();

		} else {
			throw new \SMW\InvalidResultException( 'SMWPropertiesPage expects results that are properties or errors.' );
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
	protected function formatPropertyItem( \SMW\DIProperty $property, $useCount ) {

		$linker = smwfGetLinker();

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
				list( $typestring, $proplink ) = $this->getUserDefinedPropertyInfo( $title, $property, $useCount, $linker );
			}

		} else {
			list( $typestring, $proplink ) = $this->getPredefinedPropertyInfo( $property, $linker );
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
	 * @param Linker $linker
	 *
	 * @return array
	 */
	private function getUserDefinedPropertyInfo( $title, $property, $useCount, $linker ) {

		if ( $useCount <= $this->settings->get( 'smwgPropertyLowUsageThreshold' ) ) {
			$this->getMessageFormatter()->addFromKey( 'smw_propertyhardlyused' );
		}

		// User defined types default to Page
		$typestring = SMWTypesValue::newFromTypeId( $this->settings->get( 'smwgPDefaultType' ) )->getLongHTMLText( $linker );

		$label = htmlspecialchars( $property->getLabel() );

		if ( $title->exists() ) {

			$typeProperty = new \SMW\DIProperty( '_TYPE' );
			$types = $this->store->getPropertyValues( $property->getDiWikiPage(), $typeProperty );

			if ( count( $types ) >= 1 ) {

				$typeDataValue = \SMW\DataValueFactory::newDataItemValue( current( $types ), $typeProperty );
				$typestring = $typeDataValue->getLongHTMLText( $linker );

			} else {

				$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typestring );
			}

			$proplink = $linker->link( $title, $label );

		} else {

			$this->getMessageFormatter()->addFromKey( 'smw_propertylackspage' );
			$proplink = $linker->link( $title, $label, array(), array( 'action' => 'view' ) );
		}

		return array( $typestring, $proplink );
	}

	/**
	 * Returns information related to predefined properties
	 *
	 * @since 1.9
	 *
	 * @param DIProperty $property
	 * @param Linker $linker
	 *
	 * @return array
	 */
	private function getPredefinedPropertyInfo( $property, $linker ) {
		$typestring = SMWTypesValue::newFromTypeId( $property->findPropertyTypeID() )->getLongHTMLText( $linker );
		$proplink = \SMW\DataValueFactory::newDataItemValue( $property, null )->getShortHtmlText( $linker );
		return array( $typestring, $proplink );
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of array( SMWDIProperty|SMWDIError, integer )
	 */
	function getResults( $requestOptions ) {
		$this->collector = $this->store->getPropertiesSpecial( $requestOptions );
		return $this->collector->getResults();
	}

}
