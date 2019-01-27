<?php

namespace SMW\Tests\Query\DescriptionBuilders;

use SMW\ApplicationFactory;
use SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SomeValueDescriptionBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $dataItemFactory;

	protected function setUp() {
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

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
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

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getDataItem', 'getProperty', 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				$this->equalTo( false ) );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue($this->dataItemFactory->newDITime( 1, '1970' ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );

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

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				$this->equalTo( false ) );

		$instance = new SomeValueDescriptionBuilder();

		$instance->newDescription( $dataValue, $value );
	}

	public function testInvalidDataValueRetunsThingDescription() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$instance = new SomeValueDescriptionBuilder();

		$this->assertInstanceOf(
			'\SMW\Query\Language\ThingDescription',
			$instance->newDescription( $dataValue, 'Foo' )
		);
	}

	public function testNonStringThrowsException() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new SomeValueDescriptionBuilder();

		$this->setExpectedException( 'InvalidArgumentException' );
		$instance->newDescription( $dataValue, [] );
	}

	public function testWikiPageValueOnNonMainNamespace() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getDataItem', 'getProperty', 'setUserValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'setUserValue' )
			->with(
				$this->anything(),
				$this->equalTo( false ) );

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->any() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( '~Foo', NS_HELP ) ) );

		$dataValue->expects( $this->any() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );

		$instance = new SomeValueDescriptionBuilder();

		$this->assertInstanceOf(
			'\SMW\Query\Language\Conjunction',
			$instance->newDescription( $dataValue, 'Help:~Foo' )
		);
	}

	public function valueProvider() {

		$provider[] = [
			'Foo',
			'\SMW\Query\Language\ValueDescription'
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
