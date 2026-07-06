<?php

namespace SMW\Tests\Unit\Query\DescriptionBuilders;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\DataValue;
use SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @covers \SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class SomeValueDescriptionBuilderTest extends TestCase {

	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = ApplicationFactory::getInstance()->getDataItemFactory();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SomeValueDescriptionBuilder::class,
			new SomeValueDescriptionBuilder()
		);
	}

	public function testIsBuilderForDataValue() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SomeValueDescriptionBuilder();

		$this->assertTrue(
			$instance->isBuilderFor( $dataValue )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testNewDescription( $value, $decription ) {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getDataItem', 'getProperty', 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				false );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDITime( 1, '1970' ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$instance = new SomeValueDescriptionBuilder();

		$this->assertInstanceOf(
			$decription,
			$instance->newDescription( $dataValue, $value )
		);
	}

	/**
	 * @dataProvider likeNotLikeProvider
	 */
	public function testnNewDescriptionForLikeNotLike( $value ) {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				false );

		$instance = new SomeValueDescriptionBuilder();

		$instance->newDescription( $dataValue, $value );
	}

	public function testInvalidDataValueRetunsThingDescription() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new SomeValueDescriptionBuilder();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newDescription( $dataValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SomeValueDescriptionBuilder();

		$this->expectException( 'InvalidArgumentException' );
		$instance->newDescription( $dataValue, [] );
	}

	public function testWikiPageValueOnNonMainNamespace() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getDataItem', 'getProperty', 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				false );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( '~Foo', NS_HELP ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$instance = new SomeValueDescriptionBuilder();

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->newDescription( $dataValue, 'Help:~Foo' )
		);
	}

	public function valueProvider() {
		$provider[] = [
			'Foo',
			ValueDescription::class
		];

		return $provider;
	}

	public function likeNotLikeProvider() {
		$provider[] = [
			'~Foo'
		];

		$provider[] = [
			'!~Foo'
		];

		return $provider;
	}

}
