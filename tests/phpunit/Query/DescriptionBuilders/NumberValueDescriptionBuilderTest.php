<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataValues\NumberValue;
use SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

/**
 * @covers \SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class NumberValueDescriptionBuilderTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NumberValueDescriptionBuilder::class,
			new NumberValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForNumberValue() {
		$dataValue = $this->getMockBuilder( NumberValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new NumberValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testNewDescription( $value, $decription ) {
		$numberValue = $this->getMockBuilder( NumberValue::class )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$numberValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( new Number( 42 ) );

		$numberValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( new Property( 'Foo' ) );

		$instance = new NumberValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $numberValue, $value )
		);
	}

	public function testInvalidNumberValueReturnsThingDescription() {
		$numberValue = $this->getMockBuilder( NumberValue::class )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new NumberValueDescriptionBuilder();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newDescription( $numberValue, 'Foo' )
		);
	}

	public function valueProvider() {
		$provider[] = [
			'42',
			ValueDescription::class
		];

		$provider[] = [
			'~42',
			Conjunction::class
		];

		$provider[] = [
			'~*42*',
			Conjunction::class
		];

		$provider[] = [
			'~-42',
			Conjunction::class
		];

		return $provider;
	}

}
