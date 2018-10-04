<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\ValueFormatter;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\ValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ValueFormatterTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testGetFormattedSubject() {

		$dataItem = \SMW\DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getDataItem' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getLongHTMLText' )
			->will( $this->returnValue( 'Foo' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $dataItem ) );

		$this->assertInternalType(
			'string',
			ValueFormatter::getFormattedSubject( $dataValue )
		);
	}

	public function testGetFormattedValue() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getLongHTMLText' )
			->will( $this->returnValue( 'Foo' ) );

		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInternalType(
			'string',
			ValueFormatter::getFormattedValue( $dataValue, $propertyValue )
		);
	}

	public function testGetPropertyLabel() {

		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$propertyValue->expects( $this->once() )
			->method( 'isVisible' )
			->will( $this->returnValue( true ) );

		$propertyValue->expects( $this->once() )
			->method( 'getShortHTMLText' )
			->will( $this->returnValue( 'Foo' ) );

		$this->assertInternalType(
			'string',
			ValueFormatter::getPropertyLabel( $propertyValue )
		);
	}

}
