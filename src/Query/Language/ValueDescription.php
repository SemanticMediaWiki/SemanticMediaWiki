<?php

namespace SMW\Query\Language;

use SMW\DataValueFactory;
use SMw\DIProperty;
use SMW\Query\QueryComparator;
use SMWDataItem as DataItem;

/**
 * Description of one data value, or of a range of data values.
 *
 * Technically this usually corresponds to nominal predicates or to unary
 * concrete domain predicates in OWL which are parametrised by one constant
 * from the concrete domain.
 * In RDF, concrete domain predicates that define ranges (like "greater or
 * equal to") are not directly available.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ValueDescription extends Description {

	/**
	 * @var DataItem
	 */
	private $dataItem;

	/**
	 * @var integer element in the SMW_CMP_ enum
	 */
	private $comparator;

	/**
	 * @var null|DIProperty
	 */
	private $property = null;

	/**
	 * @param DataItem $dataItem
	 * @param null|DIProperty $property
	 * @param integer $comparator
	 */
	public function __construct( DataItem $dataItem, DIProperty $property = null, $comparator = SMW_CMP_EQ ) {
		$this->dataItem = $dataItem;
		$this->comparator = $comparator;
		$this->property = $property;
	}

	/**
	 * @deprecated Use getDataItem() and \SMW\DataValueFactory::getInstance()->newDataValueByItem() if needed. Vanishes before SMW 1.7
	 * @return DataItem
	 */
	public function getDataValue() {
		// FIXME: remove
		return $this->dataItem;
	}

	/**
	 * @return DataItem
	 */
	public function getDataItem() {
		return $this->dataItem;
	}

	/**
	 * @since  2.1
	 *
	 * @return DIProperty|null
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @return integer
	 */
	public function getComparator() {
		return $this->comparator;
	}

	/**
	 * @param bool $asValue
	 *
	 * @return string
	 */
	public function getQueryString( $asValue = false ) {
		$comparator = QueryComparator::getInstance()->getStringForComparator( $this->comparator );
		$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $this->dataItem, $this->property );

		// Signals that we don't want any precision limitation
		$dataValue->setOption( 'value.description', true );

		if ( $asValue ) {
			return $comparator . $dataValue->getWikiValue();
		}

		// this only is possible for values of Type:Page
		if ( $comparator === '' ) { // some extra care for Category: pages
			return '[[:' . $dataValue->getWikiValue() . ']]';
		}

		return '[[' . $comparator . $dataValue->getWikiValue() . ']]';
	}

	public function isSingleton() {
		return $this->comparator == SMW_CMP_EQ;
	}

	public function getSize() {
		return 1;
	}

}
