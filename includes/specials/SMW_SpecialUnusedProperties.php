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
		\SMW\Profiler::In( __METHOD__ );

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'unusedproperties' )->text() );

		$page = new SMWUnusedPropertiesPage(
			\SMW\StoreFactory::getStore(),
			\SMW\Settings::newFromGlobals()
		);
		$page->setContext( $this->getContext() );

		list( $limit, $offset ) = wfCheckLimits();
		$page->doQuery( $offset, $limit );

		// Ensure locally collected output data is pushed to the output!
		SMWOutputs::commitToOutputPage( $out );

		\SMW\Profiler::Out( __METHOD__ );
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
	 * @return String
	 * @throws InvalidResultException if the result was not of a supported type
	 */
	function formatResult( $skin, $result ) {

		if ( $result instanceof \SMW\DIProperty ) {
			return $this->formatPropertyItem( $result );
		} elseif ( $result instanceof SMWDIError ) {
			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( array( $result->getErrors() ) )
				->getHtml();
		} else {
			throw new \SMW\InvalidResultException( 'SMWUnusedPropertiesPage expects results that are properties or errors.' );
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
	protected function formatPropertyItem( \SMW\DIProperty $property ) {
		$linker = smwfGetLinker();

		// Clear formatter before invoking messages and
		// avoid having previous data to be present
		$this->getMessageFormatter()->clear();

		if ( $property->isUserDefined() ) {

			$propertyLink = $linker->link(
				$property->getDiWikiPage()->getTitle(),
				$property->getLabel()
			);

			$types = $this->store->getPropertyValues( $property->getDiWikiPage(), new \SMW\DIProperty( '_TYPE' ) );

			if ( count( $types ) >= 1 ) {
				$typeDataValue = \SMW\DataValueFactory::newDataItemValue( current( $types ), new \SMW\DIProperty( '_TYPE' ) );
			} else {
				$typeDataValue = SMWTypesValue::newFromTypeId( '_wpg' );
				$this->getMessageFormatter()->addFromKey( 'smw_propertylackstype', $typeDataValue->getLongHTMLText() );
			}

		} else {
			$typeDataValue = SMWTypesValue::newFromTypeId( $property->findPropertyTypeID() );
			$propertyLink  = \SMW\DataValueFactory::newDataItemValue( $property, null )->getShortHtmlText( $linker );
		}

		return $this->msg( 'smw_unusedproperty_template', $propertyLink, $typeDataValue->getLongHTMLText( $linker )	)->text() . ' ' .
			$this->getMessageFormatter()->getHtml();
	}

	/**
	 * Get the list of results.
	 *
	 * @param SMWRequestOptions $requestOptions
	 * @return array of SMWDIProperty|SMWDIError
	 */
	function getResults( $requestOptions ) {
		$this->collector = $this->store->getUnusedPropertiesSpecial( $requestOptions );
		return $this->collector->getResults();
	}
}
