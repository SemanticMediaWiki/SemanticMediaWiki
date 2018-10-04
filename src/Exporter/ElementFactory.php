<?php

namespace SMW\Exporter;

use RuntimeException;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpResource;
use SMWDataItem as DataItem;
use SMWDITime as DITime;
use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class ElementFactory {

	/**
	 * @var array
	 */
	private $dataItemMapper = [];

	/**
	 * @var array
	 */
	private $dataItemToElementMapper = [];

	/**
	 * @since 2.2
	 *
	 * @param integer $type
	 * @param Closure $dataItemEncoder
	 */
	public function registerDataItemMapper( $type, \Closure $dataItemEncoder ) {
		$this->dataItemMapper[$type] = $dataItemEncoder;
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
	public function newFromDataItem( DataItem $dataItem ) {

		if ( $this->dataItemMapper === [] ) {
			$this->initDataItemMap();
		}

		if ( $this->dataItemToElementMapper === [] ) {
			$this->initDataItemToElementMapper();
		}

		$element = $this->findElementByDataItem( $dataItem );

		if ( $element instanceof Element || $element === null ) {
			return $element;
		}

		throw new RuntimeException( 'Encoder did not return a valid element' );
	}

	private function findElementByDataItem( $dataItem ) {

		foreach ( $this->dataItemToElementMapper as $dataItemToElementMapper ) {
			if ( $dataItemToElementMapper->isMapperFor( $dataItem ) ) {
				return $dataItemToElementMapper->getElementFor( $dataItem );
			}
		}

		foreach ( $this->dataItemMapper as $type => $callback ) {
			if ( $type === $dataItem->getDIType() ) {
				return $callback( $dataItem );
			}
		}

		return null;
	}

	private function initDataItemToElementMapper() {
		$this->dataItemToElementMapper[] = new ConceptMapper();
	}

	private function initDataItemMap() {

		$lang = '';
		$xsdValueMapper = new XsdValueMapper();

		$this->registerDataItemMapper( DataItem::TYPE_NUMBER, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->map( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_BLOB, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->map( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_BOOLEAN, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->map( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_URI, function( $dataItem ) {
			return new ExpResource(
				$dataItem->getURI(),
				$dataItem
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_TIME, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$gregorianTime = $dataItem->getForCalendarModel( DITime::CM_GREGORIAN );
			$xsdValueMapper->map( $gregorianTime );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$gregorianTime
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_CONTAINER, function( $dataItem ) {
			return Exporter::getInstance()->makeExportData(
				$dataItem->getSemanticData()
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_WIKIPAGE, function( $dataItem ) {
			return Exporter::getInstance()->getResourceElementForWikiPage(
				$dataItem
			);
		} );

		$this->registerDataItemMapper( DataItem::TYPE_PROPERTY, function( $dataItem ) {
			return Exporter::getInstance()->getResourceElementForProperty(
				$dataItem
			);
		} );

		// Not implemented
		$this->registerDataItemMapper( DataItem::TYPE_GEO, function( $dataItem ) {
			return null;
		} );
	}

}
