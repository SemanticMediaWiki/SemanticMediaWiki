<?php

namespace SMW\Tests\MediaWiki\Specials\Browse;

use SMW\MediaWiki\Specials\Browse\ValueFormatter;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\ValueFormatter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ValueFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testGetFormattedSubject() {
		$dataItem = \SMW\DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getDataItem' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getLongHTMLText' )
			->willReturn( 'Foo' );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

		$this->assertIsString(

			ValueFormatter::getFormattedSubject( $dataValue )
		);
	}

	public function testGetFormattedValue() {
		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getLongHTMLText' )
			->willReturn( 'Foo' );

		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			ValueFormatter::getFormattedValue( $dataValue, $propertyValue )
		);
	}

	public function testGetPropertyLabel() {
		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$propertyValue->expects( $this->once() )
			->method( 'isVisible' )
			->willReturn( true );

		$propertyValue->expects( $this->once() )
			->method( 'getShortHTMLText' )
			->willReturn( 'Foo' );

		$this->assertIsString(

			ValueFormatter::getPropertyLabel( $propertyValue )
		);
	}

}
