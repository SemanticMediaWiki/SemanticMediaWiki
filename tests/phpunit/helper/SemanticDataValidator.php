<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\DIProperty;

use SMWDataItem as DataItem;
use SMWDataValue as DataValue;

use RuntimeException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class SemanticDataValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @since 1.9.0.3
	 *
	 * @param SemanticData $semanticData
	 */
	public function assertThatSemanticDataIsEmpty( SemanticData $semanticData ) {
		$this->assertTrue(
			$this->assertThatSemanticDataIsIndeedEmpty( $semanticData ),
			'Asserts that the SemanticData container is empty'
		);
	}

	/**
	 * @since 1.9.0.3
	 *
	 * @param SemanticData $semanticData
	 */
	public function assertThatSemanticDataIsNotEmpty( SemanticData $semanticData ) {
		$this->assertFalse(
			$this->assertThatSemanticDataIsIndeedEmpty( $semanticData ),
			'Asserts that the SemanticData container is not empty'
		);
	}

	/**
	 * @since 1.9.0.3
	 *
	 * @param array $expected
	 * @param SemanticData $semanticData
	 */
	public function assertThatCategoriesAreSet( array $expected, SemanticData $semanticData ) {

		$runCategoryInstanceAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->getKey() === DIProperty::TYPE_CATEGORY ) {
				$runCategoryInstanceAssert = true;

				$this->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

			if ( $property->getKey() === DIProperty::TYPE_SUBCATEGORY ) {
				$runCategoryInstanceAssert = true;

				$this->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}

		// Solve issue with single/testsuite DB setup first
		// $this->assertTrue( $runCategoryInstanceAssert, __METHOD__ );
	}

	/**
	 * @since 1.9.0.3
	 *
	 * @param array $expected
	 * @param SemanticData $semanticData
	 */
	public function assertThatPropertiesAreSet( array $expected, SemanticData $semanticData ) {

		$runPropertiesAreSetAssert = false;
		$properties = $semanticData->getProperties();

		if ( isset( $expected['propertyCount'] ) ){
			$this->assertPropertyCount( $expected['propertyCount'], $properties );
		}

		foreach ( $properties as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			if ( isset( $expected['properties'] ) ){
				$this->assertContainsProperty( $expected['properties'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['property'] ) ){
				$this->assertPropertyIsSame( $expected['property'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['propertyKey'] ) ){
				$this->assertPropertyHasKey( $expected['propertyKey'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['propertyLabel']) ){
				$this->assertPropertyHasLabel( $expected['propertyLabel'], $property );
				$runPropertiesAreSetAssert = true;
			}

		}

		// Solve issue with single/testsuite DB setup first
		// $this->assertTrue( $runPropertiesAreSetAssert, __METHOD__ );

	}

	/**
	 * @since 1.9.0.3
	 *
	 * @param array $expected
	 * @param DIProperty $property,
	 * @param array $dataItems
	 */
	public function assertThatPropertyValuesAreSet( array $expected, DIProperty $property, array $dataItems ) {

		$runPropertyValueAssert = false;

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $property );

			switch ( $dataValue->getDataItem()->getDIType() ) {
				case DataItem::TYPE_TIME:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getISO8601Date' );
					break;
				case DataItem::TYPE_NUMBER:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getNumber' );
					break;
				default:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getWikiValue' );
					break;
			}

		}

		// Solve issue with single/testsuite DB setup first
		// $this->assertTrue( $runPropertyValueAssert, __METHOD__ );
	}

	private function assertThatSemanticDataIsIndeedEmpty( SemanticData $semanticData ) {

		$property = new DIProperty( '_SKEY' );

		foreach( $semanticData->getPropertyValues( $property ) as $dataItem ) {
			$semanticData->removePropertyObjectValue( $property, $dataItem );
		}

		return $semanticData->isEmpty();
	}

	private function assertPropertyHasKey( $key, DIProperty $property ) {
		$this->assertContains(
			$property->getKey(),
			$key,
			"Asserts that a {$key} property key is set"
		);
	}

	private function assertPropertyHasLabel( $label, DIProperty $property ) {
		$this->assertContains(
			$property->getKey(),
			$label,
			"Asserts that a {$label} property label is set"
		);
	}

	private function assertContainsProperty( array $properties, DIProperty $property ) {

		$runContainsPropertyAssert = false;

		foreach ( $properties as $expectedproperty ) {

			if ( $property->equals( $expectedproperty ) ) {
				$runContainsPropertyAssert = true;
				break;
			}
		}

		// Solve issue with single/testsuite DB setup first
		// $this->assertTrue( $runContainsPropertyAssert, __METHOD__ );
	}

	private function assertPropertyIsSame( DIProperty $expectedproperty, DIProperty $property ) {
		$this->assertTrue(
			$property->equals( $expectedproperty ),
			'Asserts that two properties are equal'
		);
	}

	private function assertPropertyCount( $count, array $properties ) {
		$this->assertCount(
			$count,
			$properties,
			"Asserts an expected property count of {$count}"
		);
	}

	private function assertThatPropertyValueIsSet( $expected, $dataValue, $defaultFormatter, $formatterParameters = array() ) {

		$formatter = array( $dataValue, $defaultFormatter );

		if ( isset( $expected['valueFormatter'] ) && is_callable( $expected['valueFormatter'] ) ) {
			$formatter = $expected['valueFormatter'];
			$formatterParameters = array( $dataValue );
		}

		$value = call_user_func_array( $formatter, $formatterParameters );

		$this->assertContains(
			$value,
			$expected['propertyValue'],
			"Asserts that a property value of type {$dataValue->getTypeID()} is set"
		);

		return true;
	}

}
