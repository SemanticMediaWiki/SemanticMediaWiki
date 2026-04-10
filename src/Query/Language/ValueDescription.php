<?php

namespace SMW\Query\Language;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\DataValues\NumberValue;
use SMW\DataValues\URIValue;
use SMW\Query\QueryComparator;

/**
 * Description of one data value, or of a range of data values.
 *
 * Technically this usually corresponds to nominal predicates or to unary
 * concrete domain predicates in OWL which are parametrised by one constant
 * from the concrete domain.
 * In RDF, concrete domain predicates that define ranges (like "greater or
 * equal to") are not directly available.
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ValueDescription extends Description {

	public function __construct(
		private readonly DataItem $dataItem,
		private readonly ?Property $property = null,
		private $comparator = SMW_CMP_EQ,
	) {
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint(): string {
		$property = null;

		if ( $this->property !== null ) {
			$property = $this->property->getSerialization();
		}

		// A change to the order does also change the signature and renders a
		// different query ID
		return 'V:' . md5( $this->comparator . '|' . $this->dataItem->getHash() . '|' . $property );
	}

	/**
	 * @return DataItem
	 */
	public function getDataItem(): DataItem {
		return $this->dataItem;
	}

	/**
	 * @since  2.1
	 *
	 * @return Property|null
	 */
	public function getProperty(): ?Property {
		return $this->property;
	}

	/**
	 * @return int
	 */
	public function getComparator() {
		return $this->comparator;
	}

	/**
	 * @param bool $asValue
	 *
	 * @return string
	 */
	public function getQueryString( $asValue = false ): string {
		$comparator = QueryComparator::getInstance()->getStringForComparator(
			$this->comparator
		);

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$this->dataItem,
			$this->property
		);

		// Set option to ensure that the output doesn't alter the display
		// characteristics of a value
		$dataValue->setOption( URIValue::VALUE_RAW, true );
		$dataValue->setOption( NumberValue::NO_DISP_PRECISION_LIMIT, true );

		if ( $asValue ) {
			return $comparator . $dataValue->getWikiValue();
		}

		// this only is possible for values of Type:Page
		if ( $comparator === '' ) { // some extra care for Category: pages
			return '[[:' . $dataValue->getWikiValue() . ']]';
		}

		return '[[' . $comparator . $dataValue->getWikiValue() . ']]';
	}

	public function isSingleton(): bool {
		return $this->comparator == SMW_CMP_EQ;
	}

	public function getSize(): int {
		return 1;
	}

}
