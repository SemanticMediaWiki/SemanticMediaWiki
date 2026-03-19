<?php

namespace SMW\QueryPages;

use MediaWiki\Html\Html;
use MediaWiki\Title\Title;
use Skin;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\DataValues\TypesValue;
use SMW\Exception\PropertyNotFoundException;
use SMW\RequestOptions;
use SMW\Settings;
use SMW\SQLStore\Lookup\ListLookup;
use SMW\Store;

/**
 * Query page that provides content to Special:UnusedProperties
 *
 * @ingroup QueryPage
 *
 * @license GPL-2.0-or-later
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
	public function getName(): string {
		return "UnusedProperties";
	}

	/**
	 * @codeCoverageIgnore
	 * @return bool
	 */
	public function isExpensive(): bool {
		// Disables caching for now
		return false;
	}

	/**
	 * @codeCoverageIgnore
	 * @return bool
	 */
	public function isSyndicated(): bool {
		// TODO: why not?
		return false;
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
			return $this->msg(
				'smw-sp-properties-cache-info',
				$this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() )
			)->parse();
		}

		return '';
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function getPageHeader(): string {
		return Html::rawElement(
			'p',
			[ 'class' => 'smw-unusedproperties-docu' ],
			$this->msg( 'smw-unusedproperties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property', '' ), $this->getCacheInfo() ) .
		Html::element(
			'h2',
			[],
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
	}

	/**
	 * Format a result in the list of results as a string. We expect the
	 * result to be an object of type Property (normally) or maybe
	 * Error (if something went wrong).
	 *
	 * @param Skin $skin provided by MediaWiki, not needed here
	 * @param mixed $result
	 *
	 * @return string
	 * @throws PropertyNotFoundException if the result was not of a supported type
	 */
	public function formatResult( $skin, $result ) {
		if ( $result instanceof Property ) {
			return $this->formatPropertyItem( $result );
		} elseif ( $result instanceof Error ) {
			return $this->getMessageFormatter()->clear()
				->setType( 'warning' )
				->addFromArray( [ $result->getErrors() ] )
				->getHtml();
		}

		throw new PropertyNotFoundException(
			'UnusedPropertiesQueryPage expects results that are properties or errors.'
		);
	}

	/**
	 * Produce a formatted string representation for showing a property in
	 * the list of unused properties.
	 *
	 * @since 1.8
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	protected function formatPropertyItem( Property $property ): string {
		// Clear formatter before invoking messages and
		// avoid having previous data to be present
		$this->getMessageFormatter()->clear();

		if ( $property->isUserDefined() ) {

			$title = $property->getDiWikiPage()->getTitle();

			if ( !$title instanceof Title ) {
				return '';
			}

			$propertyLink = $this->getLinker()->link(
				$title,
				$property->getLabel()
			);

			$types = $this->store->getPropertyValues(
				$property->getDiWikiPage(), new Property( '_TYPE' )
			);

			if ( is_array( $types ) && count( $types ) >= 1 ) {
				$typeDataValue = DataValueFactory::getInstance()
					->newDataValueByItem( current( $types ), new Property( '_TYPE' ) );
			} else {
				$typeDataValue = TypesValue::newFromTypeId( '_wpg' );
				$this->getMessageFormatter()
					 ->addFromKey( 'smw_propertylackstype', $typeDataValue->getLongHTMLText() );
			}

		} else {
			$typeDataValue = TypesValue::newFromTypeId( $property->findPropertyTypeID() );
			$propertyLink  = DataValueFactory::getInstance()
				->newDataValueByItem( $property, null )
				->getShortHtmlText( $this->getLinker() );
		}

		return $this->msg(
			'smw-unusedproperty-template', $propertyLink,
			$typeDataValue->getLongHTMLText( $this->getLinker() )
		)->text() . ' ' . $this->getMessageFormatter()->getHtml();
	}

	/**
	 * Get the list of results.
	 *
	 * @param RequestOptions $requestOptions
	 * @return array of Property|Error
	 */
	public function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getUnusedPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}
}

/**
 * @deprecated since 7.0.0
 */
class_alias( UnusedPropertiesQueryPage::class, 'SMW\UnusedPropertiesQueryPage' );
