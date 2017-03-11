<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\Exporter\ResourceBuilder;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMWExporter as Exporter;
use SMWDataItem as DataItem;
use SMWExpData as ExpData;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueResourceBuilder implements ResourceBuilder {

	/**
	 * @var Exporter
	 */
	protected $exporter;

	/**
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @since 2.5
	 *
	 * @param Exporter|null $exporter
	 */
	public function __construct( Exporter $exporter = null ) {
		$this->exporter = $exporter;

		if ( $this->exporter === null ) {
			$this->exporter = Exporter::getInstance();
		}

		$this->inMemoryPoolCache = ApplicationFactory::getInstance()->getInMemoryPoolCache()->getPoolCacheById(
			Exporter::POOLCACHE_ID
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isResourceBuilderFor( DIProperty $property ) {
		return true;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$expElement = $this->exporter->getDataItemExpElement(
			$dataItem
		);

		if ( $expElement !== null ) {
			$expData->addPropertyObjectValue(
				$this->getResourceElementForProperty( $property ),
				$expElement
			);
		}

		$this->addResourceHelperValue(
			$expData,
			$property,
			$dataItem
		);
	}

	protected function addResourceHelperValue( ExpData $expData, DIProperty $property, DataItem $dataItem ) {

		$expElementHelper = $this->exporter->getDataItemHelperExpElement(
			$dataItem
		);

		if ( $expElementHelper !== null ) {
			$expData->addPropertyObjectValue(
				$this->getResourceElementHelperForProperty( $property ),
				$expElementHelper
			);
		}
	}

	protected function getResourceElementForProperty( $property ) {

		$key = 'resource:builder:' . $property->getKey();

		if ( ( $resourceElement = $this->inMemoryPoolCache->fetch( $key ) ) !== false ) {
			return $resourceElement;
		}

		$resourceElement = $this->exporter->getResourceElementForProperty( $property );

		$this->inMemoryPoolCache->save(
			$key,
			$resourceElement
		);

		return $resourceElement;
	}

	protected function getResourceElementHelperForProperty( $property ) {

		$key = 'resource:builder:aux:' . $property->getKey();

		if ( ( $resourceElement = $this->inMemoryPoolCache->fetch( $key ) ) !== false ) {
			return $resourceElement;
		}

		$resourceElement = $this->exporter->getResourceElementForProperty( $property, true );

		$this->inMemoryPoolCache->save(
			$key,
			$resourceElement
		);

		return $resourceElement;
	}

}
