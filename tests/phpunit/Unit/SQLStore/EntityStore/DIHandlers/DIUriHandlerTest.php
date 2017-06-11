<?php

namespace SMW\Tests\SQLStore\EntityStore\DIHandlers;

use SMW\SQLStore\EntityStore\DIHandlers\DIUriHandler;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDIUri as DIUri;

/**
 * @covers \SMW\SQLStore\EntityStore\DIHandlers\DIUriHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DIUriHandlerTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DIUriHandler::class,
			new DIUriHandler( $this->store )
		);
	}

	public function testImmutableMethodAccess() {

		$instance = new DIUriHandler(
			$this->store
		);

		$this->assertInternalType(
			'array',
			$instance->getTableFields()
		);

		$this->assertInternalType(
			'array',
			$instance->getFetchFields()
		);

		$this->assertInternalType(
			'array',
			$instance->getTableIndexes()
		);

		$this->assertInternalType(
			'string',
			$instance->getIndexField()
		);

		$this->assertInternalType(
			'string',
			$instance->getLabelField()
		);
	}

	public function testMutableMethodAccess() {

		$uri = new DIUri( 'http', 'example.org', '', '' );

		$instance = new DIUriHandler(
			$this->store
		);

		$this->assertInternalType(
			'array',
			$instance->getWhereConds( $uri )
		);

		$this->assertInternalType(
			'array',
			$instance->getInsertValues( $uri )
		);
	}

	public function testtMutableOnNoCaseFieldFeature() {

		$instance = new DIUriHandler(
			$this->store
		);

		$expected = [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => FieldType::FIELD_TITLE
		];

		$this->assertEquals(
			$expected,
			$instance->getTableFields()
		);

		$this->assertEquals(
			$expected,
			$instance->getFetchFields()
		);

		$instance->setFieldTypeFeatures(
			SMW_FIELDT_CHAR_NOCASE
		);

		$expected = [
			'o_blob' => FieldType::TYPE_BLOB,
			'o_serialized' => FieldType::TYPE_CHAR_NOCASE
		];

		$this->assertEquals(
			$expected,
			$instance->getTableFields()
		);

		$this->assertEquals(
			$expected,
			$instance->getFetchFields()
		);
	}

	/**
	 * @dataProvider dbKeysProvider
	 */
	public function testDataItemFromDBKeys( $dbKeys ) {

		$instance = new DIUriHandler(
			$this->store
		);

		$this->assertInstanceOf(
			'\SMWDIUri',
			$instance->dataItemFromDBKeys( $dbKeys )
		);
	}

	/**
	 * @dataProvider dbKeysExceptionProvider
	 */
	public function testDataItemFromDBKeysThrowsException( $dbKeys ) {

		$instance = new DIUriHandler(
			$this->store
		);

		$this->setExpectedException( '\SMW\SQLStore\EntityStore\Exception\DataItemHandlerException' );
		$instance->dataItemFromDBKeys( $dbKeys );
	}

	public function dbKeysProvider() {

		$provider[] = array(
			array( 'http://example.org', '' )
		);

		$provider[] = array(
			array( '', 'http://example.org' )
		);

		return $provider;
	}

	public function dbKeysExceptionProvider() {

		$provider[] = array(
			array( '' )
		);

		return $provider;
	}

}
