<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\DataItemHandlerDispatcher;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlerDispatcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DataItemHandlerDispatcherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\DataItemHandlerDispatcher',
			new DataItemHandlerDispatcher( $store )
		);
	}

	/**
	 * @dataProvider dataItemTypeProvider
	 */
	public function testGetHandlerByType( $type, $expected ) {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemHandlerDispatcher( $store );

		$this->assertInstanceOf(
			$expected,
			$instance->getHandlerByType( $type )
		);
	}

	/**
	 * @dataProvider invalidTypeProvider
	 */
	public function testGetHandlerByInvalidTypeThrowsException( $type ) {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemHandlerDispatcher( $store );

		$this->setExpectedException( 'SMW\SQLStore\EntityStore\Exception\DataItemHandlerException' );
		$instance->getHandlerByType( $type );
	}

	public function dataItemTypeProvider() {

		$provider[] = array(
			DataItem::TYPE_NUMBER,
			'\SMW\SQLStore\EntityStore\DIHandlers\DINumberHandler'
		);

		$provider[] = array(
			DataItem::TYPE_BLOB,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIBlobHandler'
		);

		$provider[] = array(
			DataItem::TYPE_BOOLEAN,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIBooleanHandler'
		);

		$provider[] = array(
			DataItem::TYPE_URI,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIUriHandler'
		);

		$provider[] = array(
			DataItem::TYPE_TIME,
			'\SMW\SQLStore\EntityStore\DIHandlers\DITimeHandler'
		);

		$provider[] = array(
			DataItem::TYPE_GEO,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIGeoCoordinateHandler'
		);

		$provider[] = array(
			DataItem::TYPE_WIKIPAGE,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIWikiPageHandler'
		);

		$provider[] = array(
			DataItem::TYPE_CONCEPT,
			'\SMW\SQLStore\EntityStore\DIHandlers\DIConceptHandler'
		);

		return $provider;
	}

	public function invalidTypeProvider() {

		$provider[] = array(
			DataItem::TYPE_PROPERTY,
		);

		$provider[] = array(
			DataItem::TYPE_CONTAINER,
		);

		$provider[] = array(
			DataItem::TYPE_ERROR,
		);

		$provider[] = array(
			'Foo',
		);

		return $provider;
	}

}
