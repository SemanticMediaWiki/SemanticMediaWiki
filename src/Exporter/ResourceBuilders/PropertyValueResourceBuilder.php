<?php

namespace SMW\Exporter\ResourceBuilders;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\Export\ExpData;
use SMW\Export\Exporter;
use SMW\Exporter\ResourceBuilder;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueResourceBuilder implements ResourceBuilder {

	protected ?Exporter $exporter;

	/**
	 * @var InMemoryPoolCache
	 */
	private $inMemoryPoolCache;

	/**
	 * @since 2.5
	 *
	 * @param Exporter|null $exporter
	 */
	public function __construct( ?Exporter $exporter = null ) {
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
	public function isResourceBuilderFor( Property $property ): bool {
		return true;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function addResourceValue( ExpData $expData, Property $property, DataItem $dataItem ): void {
		$expElement = $this->exporter->newExpElement(
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

	protected function addResourceHelperValue( ExpData $expData, Property $property, DataItem $dataItem ) {
		return $this->addAuxiliaryResourceValue( $expData, $property, $dataItem );
	}

	protected function addAuxiliaryResourceValue( ExpData $expData, Property $property, DataItem $dataItem ) {
		$auxiliaryExpElement = $this->exporter->newAuxiliaryExpElement(
			$dataItem
		);

		if ( $auxiliaryExpElement === null ) {
			return;
		}

		$expData->addPropertyObjectValue(
			$this->getResourceElementHelperForProperty( $property ),
			$auxiliaryExpElement
		);
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
