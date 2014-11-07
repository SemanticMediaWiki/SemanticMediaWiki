<?php

namespace SMW\Tests\Query;

use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMW\DIProperty;

/**
 * @covers \SMWPrintRequest
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PrintRequestTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructPropertyPrintRequest() {

		$propertyValue = $this->getMockBuilder( '\SMWPropertyValue' )
			->disableOriginalConstructor()
			->getMock();

		$propertyValue->expects( $this->once() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$this->assertInstanceOf(
			'\SMWPrintRequest',
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);
	}

	public function testSetLabel() {

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( new DIProperty( 'Foo' ) );

		$instance = new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue );

		$this->assertEquals(
			null,
			$instance->getLabel()
		);

		$this->assertEquals(
			null,
			$instance->getWikiText()
		);

		$instance->setLabel( 'Bar' );

		$this->assertEquals(
			'Bar',
			$instance->getLabel()
		);

		$this->assertEquals(
			'Bar',
			$instance->getWikiText()
		);
	}

}
