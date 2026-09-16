<?php

namespace SMW\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValues\PropertyValue;
use SMW\Query\PrintRequest;

/**
 * @covers SMW\Query\PrintRequest
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class PrintRequestTest extends TestCase {

	public function testCanConstructPropertyPrintRequest() {
		$propertyValue = $this->getMockBuilder( PropertyValue::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyValue->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( true );

		$this->assertInstanceOf(
			PrintRequest::class,
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);
	}

	public function testSetLabel() {
		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( new Property( 'Foo' ) );

		$instance = new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue );

		$this->assertEquals(
			'Foo',
			$instance->getCanonicalLabel()
		);

		$this->assertNull(
			$instance->getLabel()
		);

		$this->assertNull(
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
			PrintRequest::class,
			$instance
		);

		$this->assertEquals(
			$expectedLabel,
			$instance->getLabel()
		);
	}

	public function testGetPropertyForPropertyPrintRequest() {
		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( new Property( 'Foo' ) );

		$instance = new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue );

		$this->assertEquals(
			new Property( 'Foo' ),
			$instance->getProperty()
		);
	}

	public function testGetPropertyForChainReturnsLastLinkOfTheChain() {
		$instance = PrintRequest::newFromText( 'Foo.Bar.Baz' );

		$this->assertSame(
			'Baz',
			$instance->getProperty()->getKey()
		);
	}

	/**
	 * @dataProvider printRequestWithoutPropertyProvider
	 */
	public function testGetPropertyForPrintRequestWithoutProperty( PrintRequest $instance ) {
		$this->assertNull(
			$instance->getProperty()
		);
	}

	/**
	 * @dataProvider printRequestWithoutPropertyProvider
	 */
	public function testGetTypeIDForPrintRequestWithoutProperty( PrintRequest $instance ) {
		$this->assertSame(
			'_wpg',
			$instance->getTypeID()
		);
	}

	public function printRequestWithoutPropertyProvider() {
		yield 'this' => [ new PrintRequest( PrintRequest::PRINT_THIS, 'Foo' ) ];
		yield 'categories' => [ new PrintRequest( PrintRequest::PRINT_CATS, 'Foo' ) ];
		yield 'category check' => [
			new PrintRequest( PrintRequest::PRINT_CCAT, 'Foo', WikiPage::newFromText( 'Bar' )->getTitle() )
		];
	}

	public function testGetTypeIDForChainUsesLastLinkOfTheChain() {
		$instance = PrintRequest::newFromText( 'Foo.Bar.Baz' );
		$instance->getProperty()->setPropertyValueType( '_num' );

		$this->assertSame(
			'_num',
			$instance->getTypeID()
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
		# 0
		$provider[] = [
			'Foo',
			false,
			'Foo'
		];

		# 1
		$provider[] = [
			'Foo',
			true,
			''
		];

		# 2
		$provider[] = [
			'Foo=Bar',
			false,
			'Bar'
		];

		# 3
		$provider[] = [
			'Foo=Bar#123',
			false,
			'Bar#123'
		];

		# 4
		$provider[] = [
			'Foo#123=Bar',
			false,
			'Bar'
		];

		# 5
		$provider[] = [
			'Category=Foo',
			false,
			'Foo'
		];

		# 6
		$provider[] = [
			'-Foo',
			false,
			'-Foo'
		];

		# 7
		$provider[] = [
			'-Foo=Bar',
			false,
			'Bar'
		];

		# 8, 1464
		$provider[] = [
			'Has boolean#<span style="color: green; font-size: 120%;">&#10003;</span>,<span style="color: #AA0000; font-size: 120%;">&#10005;</span>=Label on (&#10003;,&#10005;)',
			false,
			'Label on (&#10003;,&#10005;)'
		];

		return $provider;
	}

}
