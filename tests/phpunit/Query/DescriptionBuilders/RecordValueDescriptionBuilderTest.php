<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DIProperty;
use SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;

/**
 * @covers \SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class RecordValueDescriptionBuilderTest extends TestCase {

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
			->willReturnCallback( static function ( $value ) {
				 return explode( ';', $value );
			} );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->willReturn( $propertyDataItems );

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
			->willReturn( false );

		$recordValue->expects( $this->any() )
			->method( 'getPropertyDataItems' )
			->willReturn( [] );

		$instance = new RecordValueDescriptionBuilder();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newDescription( $recordValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {
		$recordValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new RecordValueDescriptionBuilder();

		$this->expectException( 'InvalidArgumentException' );
		$instance->newDescription( $recordValue, [] );
	}

	public function valueProvider() {
		$provider[] = [
			'Jan;1970',
			[ new DIProperty( 'Foo' ) ],
			SomeProperty::class
		];

		$provider[] = [
			'Jan;1970',
			[ new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ],
			Conjunction::class
		];

		$provider[] = [
			'?',
			[ new DIProperty( 'Foo' ), new DIProperty( 'Bar' ) ],
			ThingDescription::class
		];

		$provider[] = [
			'',
			[],
			ThingDescription::class
		];

		return $provider;
	}

}
