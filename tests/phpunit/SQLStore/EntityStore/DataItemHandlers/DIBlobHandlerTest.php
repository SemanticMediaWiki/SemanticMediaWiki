<?php

namespace SMW\Tests\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandlers\DIBlobHandler;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\Tests\PHPUnitCompat;
use SMWDIBlob as DIBlob;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DIBlobHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class DIBlobHandlerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DIBlobHandler::class,
			new DIBlobHandler( $this->store )
		);
	}

	public function testImmutableMethodAccess() {
		$instance = new DIBlobHandler(
			$this->store
		);

		$this->assertIsArray(

			$instance->getTableFields()
		);

		$this->assertIsArray(

			$instance->getFetchFields()
		);

		$this->assertIsArray(

			$instance->getTableIndexes()
		);

		$this->assertIsString(

			$instance->getIndexField()
		);

		$this->assertIsString(

			$instance->getLabelField()
		);
	}

	public function testMutableMethodAccess() {
		$blob = new DIBlob( 'Foo' );

		$instance = new DIBlobHandler(
			$this->store
		);

		$this->assertIsArray(

			$instance->getWhereConds( $blob )
		);

		$this->assertIsArray(

			$instance->getInsertValues( $blob )
		);
	}

	/**
	 * @dataProvider fieldTypeProvider
	 */
	public function testMutableOnFieldTypeFeature( $fieldTypeFeatures, $expected ) {
		$instance = new DIBlobHandler(
			$this->store
		);

		$instance->setFieldTypeFeatures(
			$fieldTypeFeatures
		);

		$this->assertEquals(
			$expected,
			$instance->getTableFields()
		);

		$this->assertEquals(
			$expected,
			$instance->getFetchFields()
		);
	}

	public function testMutableInsertValuesOnVariableLength() {
		$instance = new DIBlobHandler( $this->store );

		$s72  = 'zcqaBHr1jV7mINGovktU8bD6zYjgKMqfaCxQlPcT4J6h4197dQpSW5PK5f8HigRk0yEsLC2F';
		$blob = new DIBlob( $s72 );

		$expected = [
			'o_blob' => '',
			'o_hash' => $blob->getString()
		];

		$this->assertEquals(
			$expected,
			$instance->getInsertValues( $blob )
		);

		$s73  = 'zcqaBHr1jV7mINGovktU8bD6zYjgKMqfaCxQlPcT4J6h4197dQpSW5PK5f8HigRk0yEsLC2Fs';
		$blob = new DIBlob( $s73 );

		$expected = [
			'o_blob' => $blob->getString(),
			'o_hash' => 'zcqaBHr1jV7mINGovktU8bD6zYjgKMqfaCxQlPcTcf085df3633862a2d74e393fa84944e2'
		];

		$this->assertEquals(
			$expected,
			$instance->getInsertValues( $blob )
		);
	}

	/**
	 * @dataProvider dbKeysProvider
	 */
	public function testDataItemFromDBKeys( $dbKeys ) {
		$instance = new DIBlobHandler(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMWDIBlob',
			$instance->dataItemFromDBKeys( $dbKeys )
		);
	}

	/**
	 * @dataProvider dbKeysExceptionProvider
	 */
	public function testDataItemFromDBKeysThrowsException( $dbKeys ) {
		$instance = new DIBlobHandler(
			$this->store
		);

		$this->expectException( '\SMW\SQLStore\EntityStore\Exception\DataItemHandlerException' );
		$instance->dataItemFromDBKeys( $dbKeys );
	}

	public function dbKeysProvider() {
		$provider[] = [
			[ 'Foo', '' ]
		];

		$provider[] = [
			[ '', 'Foo' ]
		];

		return $provider;
	}

	public function dbKeysExceptionProvider() {
		$provider[] = [
			[ '' ]
		];

		return $provider;
	}

	private function createRandomString( $length = 10 ) {
		return substr( str_shuffle( str_repeat( $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil( $length / strlen( $x ) ) ) ), 1, $length );
	}

	public function fieldTypeProvider() {
		$provider[] = [
			SMW_FIELDT_NONE,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_hash' => FieldType::FIELD_TITLE
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_NOCASE,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_hash' => FieldType::TYPE_CHAR_NOCASE
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_LONG,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_hash' => FieldType::TYPE_CHAR_LONG
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_hash' => FieldType::TYPE_CHAR_LONG_NOCASE
			]
		];

		return $provider;
	}

}
