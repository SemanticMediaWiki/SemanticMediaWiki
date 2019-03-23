<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMWDataItem as DataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DataItemHandlerFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DataItemHandlerFactory::class,
			new DataItemHandlerFactory( $store )
		);
	}

	/**
	 * @dataProvider dataItemTypeProvider
	 */
	public function testGetHandlerByType( $type, $expected ) {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemHandlerFactory( $store );

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

		$instance = new DataItemHandlerFactory( $store );

		$this->setExpectedException( 'SMW\SQLStore\EntityStore\Exception\DataItemHandlerException' );
		$instance->getHandlerByType( $type );
	}

	public function dataItemTypeProvider() {

		$provider[] = [
			DataItem::TYPE_NUMBER,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DINumberHandler'
		];

		$provider[] = [
			DataItem::TYPE_BLOB,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIBlobHandler'
		];

		$provider[] = [
			DataItem::TYPE_BOOLEAN,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIBooleanHandler'
		];

		$provider[] = [
			DataItem::TYPE_URI,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler'
		];

		$provider[] = [
			DataItem::TYPE_TIME,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DITimeHandler'
		];

		$provider[] = [
			DataItem::TYPE_GEO,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIGeoCoordinateHandler'
		];

		$provider[] = [
			DataItem::TYPE_WIKIPAGE,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler'
		];

		$provider[] = [
			DataItem::TYPE_CONCEPT,
			'\SMW\SQLStore\EntityStore\DataItemHandlers\DIConceptHandler'
		];

		return $provider;
	}

	public function invalidTypeProvider() {

		$provider[] = [
			DataItem::TYPE_PROPERTY,
		];

		$provider[] = [
			DataItem::TYPE_CONTAINER,
		];

		$provider[] = [
			DataItem::TYPE_ERROR,
		];

		$provider[] = [
			'Foo',
		];

		return $provider;
	}

}
