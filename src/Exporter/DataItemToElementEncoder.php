<?php

namespace SMW\Exporter;

use RuntimeException;
use SMW\Exporter\Element;
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
class DataItemToElementEncoder {

	/**
	 * @var array
	 */
	private $dataItemEncoderMap = array();

	/**
	 * @var array
	 */
	private $dataItemToElementMapper = array();

	/**
	 * @since 2.2
	 *
	 * @param integer $dataItemType
	 * @param Closure $dataItemEncoder
	 */
	public function registerDataItemEncoder( $dataItemType, \Closure $dataItemEncoder ) {
		$this->dataItemEncoderMap[$dataItemType] = $dataItemEncoder;
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
	public function mapDataItemToElement( DataItem $dataItem ) {

		if ( $this->dataItemEncoderMap === array() ) {
			$this->initDataItemEncoderMap();
		}

		if ( $this->dataItemToElementMapper === array() ) {
			$this->initDataItemToElementMapper();
		}

		$element = $this->tryToMapDataItem( $dataItem );

		if ( $element instanceof Element || $element === null ) {
			return $element;
		}

		throw new RuntimeException( 'Encoder did not return a valid element' );
	}

	private function tryToMapDataItem( $dataItem ) {

		foreach ( $this->dataItemToElementMapper as $dataItemToElementMapper ) {
			if ( $dataItemToElementMapper->isMapperFor( $dataItem ) ) {
				return $dataItemToElementMapper->getElementFor( $dataItem );
			}
		}

		foreach ( $this->dataItemEncoderMap as $dataItemType => $dataItemEncoder ) {
			if ( $dataItemType === $dataItem->getDIType() ) {
				return $dataItemEncoder( $dataItem );
			}
		}

		return null;
	}

	private function initDataItemToElementMapper() {
		$this->dataItemToElementMapper[] = new ConceptToExpDataMapper();
	}

	private function initDataItemEncoderMap() {

		$lang = '';
		$xsdValueMapper = new XsdValueMapper();

		$this->registerDataItemEncoder( DataItem::TYPE_NUMBER, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->process( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_BLOB, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->process( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_BOOLEAN, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$xsdValueMapper->process( $dataItem );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$dataItem
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_URI, function( $dataItem ) {
			return new ExpResource(
				$dataItem->getURI(),
				$dataItem
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_TIME, function( $dataItem ) use ( $lang, $xsdValueMapper ) {

			$gregorianTime = $dataItem->getForCalendarModel( DITime::CM_GREGORIAN );
			$xsdValueMapper->process( $gregorianTime );

			return new ExpLiteral(
				$xsdValueMapper->getXsdValue(),
				$xsdValueMapper->getXsdType(),
				$lang,
				$gregorianTime
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_CONTAINER, function( $dataItem ) {
			return Exporter::getInstance()->makeExportData(
				$dataItem->getSemanticData()
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_WIKIPAGE, function( $dataItem ) {
			return Exporter::getInstance()->getResourceElementForWikiPage(
				$dataItem
			);
		} );

		$this->registerDataItemEncoder( DataItem::TYPE_PROPERTY, function( $dataItem ) {
			return Exporter::getInstance()->getResourceElementForProperty(
				$dataItem
			);
		} );

		// Not implemented
		$this->registerDataItemEncoder( DataItem::TYPE_GEO, function( $dataItem ) {
			return null;
		} );
	}

}
