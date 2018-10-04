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
			'Foo',
			$instance->getCanonicalLabel()
		);

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

		$this->assertEquals(
			'Foo',
			$instance->getCanonicalLabel()
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

	public function testRemoveParameter() {

		$instance = PrintRequest::newFromText( 'Foo' );
		$instance->setParameter( 'foo', 123 );

		$this->assertEquals(
			[
				'foo' => 123
			],
			$instance->getParameters()
		);

		$instance->removeParameter( 'foo' );

		$this->assertEquals(
			[],
			$instance->getParameters()
		);
	}

	public function textProvider() {

		#0
		$provider[] = [
			'Foo',
			false,
			'Foo'
		];

		#1
		$provider[] = [
			'Foo',
			true,
			''
		];

		#2
		$provider[] = [
			'Foo=Bar',
			false,
			'Bar'
		];

		#3
		$provider[] = [
			'Foo=Bar#123',
			false,
			'Bar#123'
		];

		#4
		$provider[] = [
			'Foo#123=Bar',
			false,
			'Bar'
		];

		#5
		$provider[] = [
			'Category=Foo',
			false,
			'Foo'
		];

		#6
		$provider[] = [
			'-Foo',
			false,
			'-Foo'
		];

		#7
		$provider[] = [
			'-Foo=Bar',
			false,
			'Bar'
		];

		#8, 1464
		$provider[] = [
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)'
		];

		return $provider;
	}

}
