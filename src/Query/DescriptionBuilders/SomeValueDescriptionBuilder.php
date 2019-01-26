<?php

namespace SMW\Query\DescriptionBuilders;

use InvalidArgumentException;
use SMW\DIWikiPage;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SomeValueDescriptionBuilder extends DescriptionBuilder {

	/**
	 * @var DataValue
	 */
	private $dataValue;

	/**
	 * @since 2.3
	 *
	 * {@inheritDoc}
	 */
	public function isBuilderFor( $serialization ) {
		return $serialization instanceof DataValue;
	}

	/**
	 * @since 2.3
	 *
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function newDescription( DataValue $dataValue, $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'Value needs to be a string' );
		}

		// https://www.w3.org/TR/html4/charset.html
		// Internally encode something like [[Help:>Foo*]] since &lt; and &gt;
		// would throw off the Title validator; apply only in combination with
		// a NS such as [[Help:>...]]
		$value = str_replace( [ ':<', ':>' ], [ ':-3C', ':-3E' ], $value );

		$this->dataValue = $dataValue;
		$property = $this->dataValue->getProperty();

		$comparator = SMW_CMP_EQ;
		$this->prepareValue( $property, $value, $comparator );

		$this->dataValue->setOption(
			DataValue::OPT_QUERY_COMP_CONTEXT,
			( $comparator !== SMW_CMP_EQ && $comparator !== SMW_CMP_NEQ )
		);

		$this->dataValue->setUserValue( $value );

		if ( !$this->dataValue->isValid() ) {
			return $this->descriptionFactory->newThingDescription();
		}

		$dataItem = $this->dataValue->getDataItem();

		$description = $this->descriptionFactory->newValueDescription(
			$dataItem,
			$property,
			$comparator
		);

		// Ensure [[>Help:Foo]] === [[Help:>Foo]] / [[Help:~Foo*]] === [[~Help:Foo*]]
		if ( $dataItem instanceof DIWikiPage && $dataItem->getNamespace() !== NS_MAIN ) {
			$description = $this->makeDescription( $comparator, $property, $dataItem, $description );
		}

		return $description;
	}

	private function makeDescription( $comparator, $property, $dataItem, $description ) {

		$value = $dataItem->getDBKey();

		// Normalize a possible earlier encoded string part in order for the
		// QueryComparator::extractComparatorFromString to work its magic
		if ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {
			$value = str_replace( [ '-3C', '-3E' ], [ '<', '>' ], $value );
			$this->prepareValue( $property, $value, $comparator );
		}

		// No approximate, use the normal ValueDescription
		if ( $comparator === SMW_CMP_EQ || $comparator === SMW_CMP_NEQ ) {
			return $description;
		}

		// The NS has been stripped, use a normal value clause in the MAIN namespace
		$valueDescription = $this->descriptionFactory->newValueDescription(
			$this->dataItemFactory->newDIWikiPage( $value, NS_MAIN ),
			null,
			$comparator
		);

		// #1652
		// Use [[Help:~Foo*]] as conjunctive description since the comparator
		// is only applied on the sortkey that contains the DBKey part
		$description = $this->descriptionFactory->newConjunction( [
			$this->descriptionFactory->newNamespaceDescription( $dataItem->getNamespace() ),
			$valueDescription
		] );

		return $description;
	}

}
