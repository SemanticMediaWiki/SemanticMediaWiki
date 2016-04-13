<?php

namespace SMW\Tests\Query;

use SMW\DIProperty;
use SMW\Query\PrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;

/**
 * @covers SMW\Query\PrintRequest
 * @group semantic-mediawiki
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
			'SMW\Query\PrintRequest',
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

	/**
	 * @dataProvider textProvider
	 */
	public function testFromText( $text, $showMode, $expectedLabel ) {

		$instance = PrintRequest::newFromText( $text, $showMode );

		$this->assertInstanceOf(
			'\SMW\Query\PrintRequest',
			$instance
		);

		$this->assertEquals(
			$expectedLabel,
			$instance->getLabel()
		);
	}

	public function testFromTextToReturnNullOnInvalidText() {

		$instance = PrintRequest::newFromText( '--[[Foo' );

		$this->assertNull(
			$instance
		);
	}

	public function textProvider() {

		#0
		$provider[] = array(
			'Foo',
			false,
			'Foo'
		);

		#1
		$provider[] = array(
			'Foo',
			true,
			''
		);

		#2
		$provider[] = array(
			'Foo=Bar',
			false,
			'Bar'
		);

		#3
		$provider[] = array(
			'Foo=Bar#123',
			false,
			'Bar#123'
		);

		#4
		$provider[] = array(
			'Foo#123=Bar',
			false,
			'Bar'
		);

		#5
		$provider[] = array(
			'Category=Foo',
			false,
			'Foo'
		);

		#6
		$provider[] = array(
			'-Foo',
			false,
			'-Foo'
		);

		#7
		$provider[] = array(
			'-Foo=Bar',
			false,
			'Bar'
		);

		#8, 1464
		$provider[] = array(
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)'
		);

		return $provider;
	}

}
