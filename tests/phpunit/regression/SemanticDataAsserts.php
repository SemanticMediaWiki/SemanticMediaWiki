<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\StoreFactory;
use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWDataItem as DataItem;
use SMWDataValue as DataValue;

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
class SemanticDataAsserts extends \PHPUnit_Framework_TestCase {

	/**
	 * @param array $expected
	 * @param SemanticData $semanticData
	 *
	 * @since 1.9.0.3
	 */
	public function assertThatPropertiesAreSet( array $expected, SemanticData $semanticData ) {

		$runPropertyAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			if ( isset( $expected['propertyKey']) ){
				$runPropertyAssert = true;

				$this->assertContains(
					$property->getKey(),
					$expected['propertyKey'],
					'Asserts that the SemanticData container contains a specific property key'
				);
			}

			if ( isset( $expected['propertyLabel']) ){
				$runPropertyAssert = true;

				$this->assertContains(
					$property->getLabel(),
					$expected['propertyLabel'],
					'Asserts that the SemanticData container contains a specific property label'
				);
			}

		}

		$this->assertTrue( $runPropertyAssert, 'Assert that properties were checked' );

	}

	/**
	 * @param array $expected
	 * @param SemanticData $semanticData
	 *
	 * @since 1.9.0.3
	 */
	public function assertThatCategoriesAreSet( array $expected, SemanticData $semanticData ) {

		$runCategoryInstanceAssert = false;

		foreach ( $semanticData->getProperties() as $property ) {

			if ( $property->getKey() === DIProperty::TYPE_CATEGORY && $property->getKey() === $expected['propertyKey'] ) {
				$runCategoryInstanceAssert = true;

				$this->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

			if ( $property->getKey() === DIProperty::TYPE_SUBCATEGORY && $property->getKey() === $expected['propertyKey'] ) {
				$runCategoryInstanceAssert = true;

				$this->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$semanticData->getPropertyValues( $property )
				);
			}

		}

		$this->assertTrue( $runCategoryInstanceAssert, 'Assert that a category instance were checked' );
	}

	/**
	 * @param array $expected
	 * @param DIProperty $property,
	 * @param array $dataItems
	 *
	 * @since 1.9.0.3
	 */
	public function assertThatPropertyValuesAreSet( array $expected, DIProperty $property, array $dataItems ) {

		$runPropertyValueAssert = false;

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue( $dataItem, $property );

			switch ( $dataValue->getDataItem()->getDIType() ) {
				case DataItem::TYPE_TIME:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getISO8601Date' );
					break;
				case DataItem::TYPE_WIKIPAGE:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getWikiValue' );
					break;
				case DataItem::TYPE_NUMBER:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getNumber' );
					break;
				case DataItem::TYPE_BLOB:
					$runPropertyValueAssert = $this->assertThatPropertyValueIsSet( $expected, $dataValue, 'getWikiValue' );
					break;
				default:
					//$runPropertyValueAssert = false;
					break;
			}

		}

		$this->assertTrue( $runPropertyValueAssert, 'Assert that property values were checked' );
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
			"Asserts that the SemanticData contains a {$dataValue->getTypeID()} value"
		);

		return true;
	}

}
