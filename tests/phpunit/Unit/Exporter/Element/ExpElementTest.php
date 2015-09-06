<?php

namespace SMW\Tests\Exporter\Element;

use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpResource;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Element\ExpLiteral;
use SMW\DIWikiPage;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\Exporter\Element\ExpElement
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpElementTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = $this->getMockBuilder( '\SMW\Exporter\Element\ExpElement' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\Exporter\Element',
			$instance
		);

		// Legacy
		$this->assertInstanceOf(
			'\SMWExpElement',
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

		$provider = array();

		$provider[] = array( new ExpResource( 'foo' ) );
		$provider[] = array( new ExpResource( 'foo', null ) );
		$provider[] = array( new ExpResource( 'foo', new \SMWDIBlob( 'bar' ) ) );

		$provider[] = array( new ExpNsResource( 'foo', 'bar', 'baz' ) );
		$provider[] = array( new ExpNsResource( 'foo', 'bar', 'baz', null ) );
		$provider[] = array( new ExpNsResource( 'foo', 'bar', 'baz', new \SMWDIBlob( 'bar' ) ) );

		$provider[] = array( new ExpLiteral( 'foo' ) );
		$provider[] = array( new ExpLiteral( 'foo', '' ) );
		$provider[] = array( new ExpLiteral( 'foo', 'bar' ) );
		$provider[] = array( new ExpLiteral( 'foo', '', '', null ) );
		$provider[] = array( new ExpLiteral( 'foo', '', '', new \SMWDIBlob( 'bar' ) ) );
		$provider[] = array( new ExpLiteral( 'foo', 'baz', '', new \SMWDIBlob( 'bar' ) ) );

		return $provider;
	}

}
