<?php

namespace SMW\Exporter\Element;

use SMW\Exporter\Element;
use SMWDataItem as DataItem;
use RuntimeException;

/**
 * ExpElement is a class for representing single elements that appear in
 * exported data, such as individual resources, data literals, or blank nodes.
 *
 * A single element for export, e.g. a data literal, instance name, or blank
 * node. This abstract base class declares the basic common functionality of
 * export elements (which is not much, really).
 * @note This class should not be instantiated directly.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
abstract class ExpElement implements Element {

	/**
	 * The DataItem that this export element is associated with, if
	 * any. Might be unset if not given yet.
	 *
	 * @var DataItem|null
	 */
	protected $dataItem;

	/**
	 * @since 1.6
	 *
	 * @param DataItem|null $dataItem
	 */
	public function __construct( DataItem $dataItem = null ) {
		$this->dataItem = $dataItem;
	}

	/**
	 * Get a DataItem object that represents the contents of this export
	 * element in SMW, or null if no such data item could be found.
	 *
	 * @return DataItem|null
	 */
	public function getDataItem() {
		return $this->dataItem;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash() {
		return md5( json_encode( $this->getSerialization() ) );
	}

	/**
	 * @since  2.2
	 *
	 * @return array
	 */
	public function getSerialization() {

		$dataItem = null;

		if ( $this->getDataItem() !== null ) {
			$dataItem = array(
				'type' => $this->getDataItem()->getDIType(),
				'item' => $this->getDataItem()->getSerialization()
			);
		}

		return array(
			'dataitem' => $dataItem
		);
	}

	/**
	 * @see ExpElement::newFromSerialization
	 */
	protected static function deserialize( $serialization ) {

		$dataItem = null;

		if ( !array_key_exists( 'dataitem', $serialization ) ) {
			throw new RuntimeException( "The serialization format is missing a dataitem element" );
		}

		// If it is null, isset will ignore it
		if ( isset( $serialization['dataitem'] ) ) {
			$dataItem = DataItem::newFromSerialization(
				$serialization['dataitem']['type'],
				$serialization['dataitem']['item']
			);
		}

		return $dataItem;
	}

	/**
	 * @since  2.2
	 *
	 * @param array $serialization
	 *
	 * @return ExpElement
	 */
	public static function newFromSerialization( array $serialization ) {

		if ( !isset( $serialization['type'] ) ) {
			throw new RuntimeException( "The serialization format is missing a type element" );
		}

		switch ( $serialization['type'] ) {
			case Element::TYPE_RESOURCE:
				$elementClass = '\SMW\Exporter\Element\ExpResource';
				break;
			case Element::TYPE_NSRESOURCE:
				$elementClass = '\SMW\Exporter\Element\ExpNsResource';
				break;
			case Element::TYPE_LITERAL:
				$elementClass = '\SMW\Exporter\Element\ExpLiteral';
				break;
			default:
				throw new RuntimeException( "Unknown type" );
		}

		$serialization['dataitem'] = self::deserialize( $serialization );

		return $elementClass::deserialize( $serialization );
	}

}
