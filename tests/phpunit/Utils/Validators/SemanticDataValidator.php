<?php

namespace SMW\Tests\Utils\Validators;

use RuntimeException;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\SemanticData;
use SMWDataItem as DataItem;

/**
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class SemanticDataValidator extends \PHPUnit_Framework_Assert {

	/**
	 * @var boolean
	 */
	private $strictModeForValueMatch = true;

	/**
	 * @param boolean $strictMode
	 */
	public function setStrictModeForValueMatch( $strictMode ) {
		$this->strictModeForValueMatch = (bool)$strictMode;
	}

	/**
	 * @since 1.9.1
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
	 * @since 1.9.1
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
	 * @since 1.9.1
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

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runCategoryInstanceAssert, __METHOD__ );
	}

	/**
	 * @since 1.9.1
	 *
	 * @param integer $count
	 * @param SemanticData $semanticData
	 * @param string|null $msg
	 */
	public function assertThatSemanticDataHasPropertyCountOf( $count, SemanticData $semanticData, $msg = null ) {

		$prop = [];

		foreach ( $semanticData->getProperties() as $property ) {
			$prop[] = $property->getKey();
		}

		$msg = $msg === null ? "Failed asserting property count of {$count}" : $msg;
		$msg .= ' Counted properties include: ' . json_encode( $prop, JSON_PRETTY_PRINT );

		$this->assertCount( $count, $prop, $msg );
	}

	/**
	 * @since 2.1
	 *
	 * @param array $expected
	 * @param array $properties
	 */
	public function assertHasProperties( array $expected, array $properties ) {

		$expected = isset( $expected['properties'] ) ? $expected['properties'] : $expected;

		foreach ( $properties as $property ) {

			foreach ( $expected as $key => $expectedProperty ) {
				if ( $property->equals( $expectedProperty ) ) {
					unset( $expected[$key] );
				}
			}
		}

		$this->assertEmpty(
			$expected,
			'Failed asserting that properties array contains [ ' . $this->formatAsString( $expected ) .' ].'
		);
	}

	/**
	 * @since 1.9.1
	 *
	 * @param array $expected
	 * @param DIProperty $property
	 */
	public function assertThatPropertyHasCharacteristicsAs( array $expected, DIProperty $property ) {

		$runAssertThatPropertyHasCharacteristicsAs = false;

		if ( isset( $expected['property'] ) ) {
			$this->assertPropertyIsSameAs( $expected['property'], $property );
			$runAssertThatPropertyHasCharacteristicsAs = true;
		}

		if ( isset( $expected['propertyKey'] ) ) {
			$this->assertEquals(
				$expected['propertyKey'],
				$property->getKey(),
				__METHOD__ . " asserts property key for {$property->getLabel()}"
			);

			$runAssertThatPropertyHasCharacteristicsAs = true;
		}

		if ( isset( $expected['propertyLabel'] ) ) {
			$this->assertEquals(
				$expected['propertyLabel'],
				$property->getLabel(),
				__METHOD__ . " asserts property label for '{$property->getKey()}'"
			);

			$runAssertThatPropertyHasCharacteristicsAs = true;
		}

		if ( isset( $expected['propertyTypeId'] ) ) {
			$this->assertEquals(
				$expected['propertyTypeId'],
				$property->findPropertyTypeID(),
				__METHOD__ . " asserts property typeId for '{$property->getKey()}'"
			);

			$runAssertThatPropertyHasCharacteristicsAs = true;
		}

		$this->assertTrue( $runAssertThatPropertyHasCharacteristicsAs, __METHOD__ );

	}

	/**
	 * Assertion array should follow:
	 * 'propertyCount'  => int
	 * 'propertyLabels' => array() or 'propertyKeys' => array()
	 * 'propertyValues' => array()
	 *
	 * @since 1.9.1
	 *
	 * @param array $expected
	 * @param SemanticData $semanticData
	 */
	public function assertThatPropertiesAreSet( array $expected, SemanticData $semanticData, $message = '' ) {

		$runPropertiesAreSetAssert = false;
		$properties = $semanticData->getProperties();

		// Deprecated, use strictPropertyValueMatch
		if ( isset( $expected['strict-mode-valuematch'] ) ) {
			$this->setStrictModeForValueMatch( $expected['strict-mode-valuematch'] );
		}

		if ( isset( $expected['strictPropertyValueMatch'] ) ) {
			$this->setStrictModeForValueMatch( $expected['strictPropertyValueMatch'] );
		}

		if ( isset( $expected['propertyCount'] ) ) {
			$this->assertThatSemanticDataHasPropertyCountOf( $expected['propertyCount'], $semanticData, $message );
		}

		$report = [
			'@unresolved' => [],
			'@valueHint' => []
		];

		foreach ( $properties as $property ) {

			$this->assertInstanceOf( '\SMW\DIProperty', $property );

			if ( isset( $expected['properties'] ) ) {
				$this->assertContainsProperty( $expected['properties'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['property'] ) ) {
				$this->assertPropertyIsSameAs( $expected['property'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['propertyKeys'] ) ) {
				$this->assertContainsPropertyKeys( $expected['propertyKeys'], $property, $message );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['propertyLabels'] ) ) {
				$this->assertContainsPropertyLabels( $expected['propertyLabels'], $property );
				$runPropertiesAreSetAssert = true;
			}

			if ( isset( $expected['propertyValues'] ) ) {
				$pv = $semanticData->getPropertyValues( $property );
				$report[$property->getKey()] =  $this->formatAsString( $pv );

				$this->assertThatPropertyValuesAreSet(
					$expected,
					$property,
					$pv
				);

				$runPropertiesAreSetAssert = true;
			}
		}

		// Final ceck for values distributed over different properties
		if ( isset( $expected['propertyValues'] ) && !$this->strictModeForValueMatch ) {
			$report['@unresolved'] = $expected['propertyValues'];
			$report['@valueHint'] = $expected['@valueHint'];
			$this->assertEmpty(
				$expected['propertyValues'],
				"Unmatched values in {$message} for:\n" . json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			);
		}

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runPropertiesAreSetAssert, __METHOD__ );

		return $runPropertiesAreSetAssert;
	}

	/**
	 * @since 1.9.1
	 *
	 * @param array $expected
	 * @param DIProperty $property,
	 * @param array $dataItems
	 */
	public function assertThatPropertyValuesAreSet( array &$expected, DIProperty $property, array $dataItems ) {

		$runPropertyValueAssert = false;

		if ( !isset( $expected['@valueHint'] ) ) {
			$expected['@valueHint'] = [];
		}

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem( $dataItem, $property );

			switch ( $dataValue->getDataItem()->getDIType() ) {
				case DataItem::TYPE_TIME:
					$runPropertyValueAssert = $this->assertContainsPropertyValues( $expected, $dataValue, 'getISO8601Date' );
					break;
				case DataItem::TYPE_NUMBER:
					$runPropertyValueAssert = $this->assertContainsPropertyValues( $expected, $dataValue, 'getNumber' );
					break;
				case DataItem::TYPE_BOOLEAN:
					$runPropertyValueAssert = $this->assertContainsPropertyValues( $expected, $dataValue, 'getBoolean' );
					break;
				default:
					$runPropertyValueAssert = $this->assertContainsPropertyValues( $expected, $dataValue, 'getWikiValue' );
					break;
			}
		}

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runPropertyValueAssert, __METHOD__ );

		return $runPropertyValueAssert;
	}

	private function assertThatSemanticDataIsIndeedEmpty( SemanticData $semanticData ) {

		$property = new DIProperty( '_SKEY' );

		foreach( $semanticData->getPropertyValues( $property ) as $dataItem ) {
			$semanticData->removePropertyObjectValue( $property, $dataItem );
		}

		return $semanticData->isEmpty();
	}

	private function assertContainsPropertyKeys( $keys, DIProperty $property, $message = null ) {

		$keys = str_replace( " ", "_", $keys );
		$message = ( $message === null ? 'Failed asserting' : "Failed asserting \"$message\"" );

		$this->assertContains(
			$property->getKey(),
			$keys,
			"{$message} property key: '{$property->getLabel()}' in ({$this->formatAsString( $keys )})"
		);
	}

	private function assertContainsPropertyLabels( $labels, DIProperty $property ) {
		$this->assertContains(
			$property->getLabel(),
			$labels,
			__METHOD__ . " asserts property label for '{$property->getKey()}' with ({$this->formatAsString( $labels )})"
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

		// Issue #124 needs to be resolved first
		// $this->assertTrue( $runContainsPropertyAssert, __METHOD__ );
	}

	private function assertPropertyIsSameAs( DIProperty $expectedproperty, DIProperty $property ) {
		$this->assertTrue(
			$property->equals( $expectedproperty ),
			__METHOD__ . ' asserts that two properties are equal'
		);
	}

	private function assertContainsPropertyValues( &$expected, $dataValue, $defaultFormatter, $formatterParameters = [] ) {

		if ( !isset( $expected['propertyValues'] ) ) {
			throw new RuntimeException( "Expected a 'propertyValues' array index" );
		}

		$formatter = [ $dataValue, $defaultFormatter ];
		$valueSerialization = $dataValue->getDataItem()->getSerialization();

		if ( isset( $expected['valueFormatter'] ) && is_callable( $expected['valueFormatter'] ) ) {
			$formatter = $expected['valueFormatter'];
			$formatterParameters = [ $dataValue ];
		}

		$value = call_user_func_array( $formatter, $formatterParameters );
		$expected['@valueHint'][] = $value;

		if ( $this->strictModeForValueMatch ) {

			$this->assertContains(
				$value,
				$expected['propertyValues'],
				__METHOD__ .
				" for '{$dataValue->getProperty()->getKey()}'" .
				" as '{$dataValue->getTypeID()}'" .
				" with ({$this->formatAsString( $expected['propertyValues'] )})"
			);

			return true;
		}

		// Be more lenient towards value comparison by just eliminating a matched pair
		foreach ( $expected['propertyValues'] as $key => $propertyValue ) {

			if ( is_bool( $value ) && $value === $propertyValue ) {
				unset( $expected['propertyValues'][$key] );
				continue;
			}

			if ( ( is_numeric( $value ) && is_numeric( $propertyValue ) )  && $value == $propertyValue ) {
				unset( $expected['propertyValues'][$key] );
				continue;
			}

			if ( strpos( $propertyValue, (string)$value ) !== false ) {
				unset( $expected['propertyValues'][$key] );
				continue;
			}

			if ( $propertyValue === $valueSerialization ) {
				unset( $expected['propertyValues'][$key] );
			}
		}

		return true;
	}

	private function formatAsString( $expected ) {
		return is_array( $expected ) ? implode( ', ', $expected ) : $expected;
	}

}
