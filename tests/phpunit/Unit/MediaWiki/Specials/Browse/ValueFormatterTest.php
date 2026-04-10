<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\Browse;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\DataValues\PropertyValue;
use SMW\MediaWiki\Specials\Browse\ValueFormatter;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Browse\ValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ValueFormatterTest extends TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testGetFormattedSubject() {
		$dataItem = WikiPage::newFromText( 'Foo', SMW_NS_PROPERTY );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getDataItem' ] )
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
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValue->expects( $this->once() )
			->method( 'getLongHTMLText' )
			->willReturn( 'Foo' );

		$propertyValue = $this->getMockBuilder( PropertyValue::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertIsString(

			ValueFormatter::getFormattedValue( $dataValue, $propertyValue )
		);
	}

	public function testGetPropertyLabel() {
		$propertyValue = $this->getMockBuilder( PropertyValue::class )
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
