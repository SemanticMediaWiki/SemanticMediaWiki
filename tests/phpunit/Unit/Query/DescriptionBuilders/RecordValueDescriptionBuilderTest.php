<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder;
use SMW\DIProperty;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class RecordValueDescriptionBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RecordValueDescriptionBuilder::class,
			new RecordValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForTimeValue() {

		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new RecordValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testNewDescription( $value, $propertyDataItems, $decription ) {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$recordValue->expects( $this->any() )
			->method( 'getValuesFromString' )
			->with( $this->stringContains( $value ) )
			->will( $this->returnCallback( function( $value ) {
				 return explode(';', $value );
			} ) );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->will( $this->returnValue( $propertyDataItems ) );

		$instance = new RecordValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $recordValue, $value )
		);
	}

	public function testInvalidRecordValueReturnsThingDescription() {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$recordValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->will( $this->returnValue( [] ) );

		$instance = new RecordValueDescriptionBuilder();

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->newDescription( $recordValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {

		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RecordValueDescriptionBuilder();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->newDescription( $recordValue, [] );
	}

	public function valueProvider() {

		$provider[] = [
			'Jan;1970',
			[ new DIProperty( 'Foo' ) ],
			'\SMW\Query\Language\SomeProperty'
		];

		$provider[] = [
			'Jan;1970',
			[ new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ],
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'?',
			[ new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ],
			'\SMW\Query\Language\ThingDescription'
		];

		$provider[] = [
			'',
			[],
			'\SMW\Query\Language\ThingDescription'
		];

		return $provider;
	}

}
