<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataValues\TimeValue;
use SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;

/**
 * @covers \SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class TimeValueDescriptionBuilderTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TimeValueDescriptionBuilder::class,
			new TimeValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForTimeValue() {
		$dataValue = $this->getMockBuilder( TimeValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new TimeValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testNewDescription( $value, $decription ) {
		$timeValue = $this->getMockBuilder( TimeValue::class )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$timeValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( new Time( 1, '1970' ) );

		$timeValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( new Property( 'Foo' ) );

		$instance = new TimeValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $timeValue, $value )
		);
	}

	public function testInvalidTimeValueReturnsThingDescription() {
		$timeValue = $this->getMockBuilder( TimeValue::class )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new TimeValueDescriptionBuilder();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newDescription( $timeValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {
		$timeValue = $this->getMockBuilder( TimeValue::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TimeValueDescriptionBuilder();

		$this->expectException( 'InvalidArgumentException' );
		$instance->newDescription( $timeValue, [] );
	}

	public function valueProvider() {
		$provider[] = [
			'Jan 1970',
			ValueDescription::class
		];

		$provider[] = [
			'~Jan 1970',
			Conjunction::class
		];

		$provider[] = [
			'!~Jan 1970',
			Disjunction::class
		];

		return $provider;
	}

}
