<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\SQLStore\EntityStore\DataItemHandlerFactory;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIBlobHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIBooleanHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIConceptHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIGeoCoordinateHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DINumberHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DITimeHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIWikiPageHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlerFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class DataItemHandlerFactoryTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
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
		$store = $this->getMockBuilder( SQLStore::class )
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
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataItemHandlerFactory( $store );

		$this->expectException( DataItemHandlerException::class );
		$instance->getHandlerByType( $type );
	}

	public function dataItemTypeProvider() {
		$provider[] = [
			DataItem::TYPE_NUMBER,
			DINumberHandler::class
		];

		$provider[] = [
			DataItem::TYPE_BLOB,
			DIBlobHandler::class
		];

		$provider[] = [
			DataItem::TYPE_BOOLEAN,
			DIBooleanHandler::class
		];

		$provider[] = [
			DataItem::TYPE_URI,
			DIUriHandler::class
		];

		$provider[] = [
			DataItem::TYPE_TIME,
			DITimeHandler::class
		];

		$provider[] = [
			DataItem::TYPE_GEO,
			DIGeoCoordinateHandler::class
		];

		$provider[] = [
			DataItem::TYPE_WIKIPAGE,
			DIWikiPageHandler::class
		];

		$provider[] = [
			DataItem::TYPE_CONCEPT,
			DIConceptHandler::class
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
