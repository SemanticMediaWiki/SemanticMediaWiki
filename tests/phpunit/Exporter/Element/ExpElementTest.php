<?php

namespace SMW\Tests\Exporter\Element;

use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpResource;

/**
 * @covers \SMW\Exporter\Element\ExpElement
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class ExpElementTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = $this->getMockBuilder( '\SMW\Exporter\Element\ExpElement' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Exporter\Element',
			$instance
		);

		$this->assertInstanceOf(
			'\SMW\Exporter\Element\ExpElement',
			$instance
		);
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetDataItem( ExpElement $element ) {
		if ( $element->getDataItem() === null ) {
			$this->assertNull(
				$element->getDataItem()
			);
		} else {
			$this->assertInstanceOf(
				'\SMWDataItem',
				$element->getDataItem()
			);
		}
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testSerielization( ExpElement $element ) {
		$serialization = ExpElement::newFromSerialization(
			$element->getSerialization()
		);

		$this->assertEquals(
			$element->getDataItem(),
			$serialization->getDataItem()
		);
	}

	public function instanceProvider() {
		$provider = [];

		$provider[] = [ new ExpResource( 'foo' ) ];
		$provider[] = [ new ExpResource( 'foo', null ) ];
		$provider[] = [ new ExpResource( 'foo', new \SMWDIBlob( 'bar' ) ) ];

		$provider[] = [ new ExpNsResource( 'foo', 'bar', 'baz' ) ];
		$provider[] = [ new ExpNsResource( 'foo', 'bar', 'baz', null ) ];
		$provider[] = [ new ExpNsResource( 'foo', 'bar', 'baz', new \SMWDIBlob( 'bar' ) ) ];

		$provider[] = [ new ExpLiteral( 'foo' ) ];
		$provider[] = [ new ExpLiteral( 'foo', '' ) ];
		$provider[] = [ new ExpLiteral( 'foo', 'bar' ) ];
		$provider[] = [ new ExpLiteral( 'foo', '', '', null ) ];
		$provider[] = [ new ExpLiteral( 'foo', '', '', new \SMWDIBlob( 'bar' ) ) ];
		$provider[] = [ new ExpLiteral( 'foo', 'baz', '', new \SMWDIBlob( 'bar' ) ) ];

		return $provider;
	}

}
