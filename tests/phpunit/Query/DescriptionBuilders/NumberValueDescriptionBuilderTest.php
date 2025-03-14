<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder;

/**
 * @covers \SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class NumberValueDescriptionBuilderTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NumberValueDescriptionBuilder::class,
			new NumberValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForNumberValue() {
		$dataValue = $this->getMockBuilder( '\SMWNumberValue' )
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
		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$numberValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( new \SMWDINumber( 42 ) );

		$numberValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( new \SMW\DIProperty( 'Foo' ) );

		$instance = new NumberValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $numberValue, $value )
		);
	}

	public function testInvalidNumberValueReturnsThingDescription() {
		$numberValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMock();

		$numberValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new NumberValueDescriptionBuilder();

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->newDescription( $numberValue, 'Foo' )
		);
	}

	public function valueProvider() {
		$provider[] = [
			'42',
			'\SMW\Query\Language\ValueDescription'
		];

		$provider[] = [
			'~42',
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'~*42*',
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'~-42',
			'\SMW\Query\Language\Conjunction'
		];

		return $provider;
	}

}
