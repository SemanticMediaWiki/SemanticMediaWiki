<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class TimeValueDescriptionBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TimeValueDescriptionBuilder::class,
			new TimeValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForTimeValue() {
		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
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
		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$timeValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( new \SMWDITime( 1, '1970' ) );

		$timeValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( new \SMW\DIProperty( 'Foo' ) );

		$instance = new TimeValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $timeValue, $value )
		);
	}

	public function testInvalidTimeValueReturnsThingDescription() {
		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$timeValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new TimeValueDescriptionBuilder();

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->newDescription( $timeValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {
		$timeValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TimeValueDescriptionBuilder();

		$this->expectException( 'InvalidArgumentException' );
		$instance->newDescription( $timeValue, [] );
	}

	public function valueProvider() {
		$provider[] = [
			'Jan 1970',
			'\SMW\Query\Language\ValueDescription'
		];

		$provider[] = [
			'~Jan 1970',
			'\SMW\Query\Language\Conjunction'
		];

		$provider[] = [
			'!~Jan 1970',
			'\SMW\Query\Language\Disjunction'
		];

		return $provider;
	}

}
