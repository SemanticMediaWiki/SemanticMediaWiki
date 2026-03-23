<?php

namespace SMW\Exporter;

use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\Time;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpResource;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ElementFactory {

	/**
	 * @var array
	 */
	private $dataItemMappers = [];

	/**
	 * @since 2.2
	 *
	 * @param int $type
	 * @param callable $dataItemMapper
	 */
	public function registerCallableMapper( $type, callable $dataItemMapper ): void {
		$this->dataItemMappers[$type] = $dataItemMapper;
	}

	/**
	 * Create an Element that encodes the data for the given dataitem object.
	 * This method is meant to be used when exporting a dataitem as a subject
	 * or object.
	 *
	 * @param DataItem $dataItem
	 *
	 * @return Element|null
	 * @throws RuntimeException
	 */
	public function newFromDataItem( DataItem $dataItem ): ?Element {
		if ( $this->dataItemMappers === [] ) {
			$this->initDefaultMappers();
		}

		$element = $this->newElement( $dataItem );

		if ( $element instanceof Element || $element === null ) {
			return $element;
		}

		throw new RuntimeException( "Couldn't map an element to " . get_class( $dataItem ) );
	}

	private function newElement( DataItem $dataItem ) {
		$type = $dataItem->getDIType();

		if ( isset( $this->dataItemMappers[$type] ) && is_callable( $this->dataItemMappers[$type] ) ) {
			return $this->dataItemMappers[$type]( $dataItem );
		}

		foreach ( $this->dataItemMappers as $service ) {
			if ( $service instanceof DataItemMapper && $service->isMapperFor( $dataItem ) ) {
				return $service->newElement( $dataItem );
			}
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpLiteral
	 */
	public function newFromNumber( DataItem $dataItem ): ExpLiteral {
		[ $type, $value ] = XsdValueMapper::map(
			$dataItem
		);

		return new ExpLiteral( $value, $type, '', $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpLiteral
	 */
	public function newFromBlob( DataItem $dataItem ): ExpLiteral {
		[ $type, $value ] = XsdValueMapper::map(
			$dataItem
		);

		return new ExpLiteral( $value, $type, '', $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpLiteral
	 */
	public function newFromBoolean( DataItem $dataItem ): ExpLiteral {
		[ $type, $value ] = XsdValueMapper::map(
			$dataItem
		);

		return new ExpLiteral( $value, $type, '', $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpResource
	 */
	public function newFromURI( DataItem $dataItem ): ExpResource {
		return new ExpResource( $dataItem->getURI(), $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpLiteral
	 */
	public function newFromTime( DataItem $dataItem ): ExpLiteral {
		$dataItem = $dataItem->getForCalendarModel( Time::CM_GREGORIAN );

		[ $type, $value ] = XsdValueMapper::map(
			$dataItem
		);

		return new ExpLiteral( $value, $type, '', $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpData
	 */
	public function newFromContainer( DataItem $dataItem ) {
		return Exporter::getInstance()->makeExportData( $dataItem->getSemanticData() );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpResource
	 */
	public function newFromWikiPage( DataItem $dataItem ) {
		return Exporter::getInstance()->getResourceElementForWikiPage( $dataItem );
	}

	/**
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 *
	 * @return ExpResource
	 */
	public function newFromProperty( DataItem $dataItem ) {
		return Exporter::getInstance()->getResourceElementForProperty( $dataItem );
	}

	/**
	 * Not implemented !
	 *
	 * @since 3.1
	 *
	 * @param DataItem $dataItem
	 */
	public function newFromGeo( DataItem $dataItem ) {
		return null;
	}

	private function initDefaultMappers(): void {
		$this->dataItemMappers[DataItem::TYPE_NUMBER] = [ $this, 'newFromNumber' ];
		$this->dataItemMappers[DataItem::TYPE_BLOB] = [ $this, 'newFromBlob' ];
		$this->dataItemMappers[DataItem::TYPE_BOOLEAN] = [ $this, 'newFromBoolean' ];
		$this->dataItemMappers[DataItem::TYPE_URI] = [ $this, 'newFromURI' ];
		$this->dataItemMappers[DataItem::TYPE_TIME] = [ $this, 'newFromTime' ];
		$this->dataItemMappers[DataItem::TYPE_CONTAINER] = [ $this, 'newFromContainer' ];
		$this->dataItemMappers[DataItem::TYPE_WIKIPAGE] = [ $this, 'newFromWikiPage' ];
		$this->dataItemMappers[DataItem::TYPE_PROPERTY] = [ $this, 'newFromProperty' ];
		$this->dataItemMappers[DataItem::TYPE_GEO] = [ $this, 'newFromGeo' ];

		$this->dataItemMappers[] = new ConceptMapper();
	}

}
