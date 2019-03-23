<?php

namespace SMW\Tests\SQLStore\EntityStore\DataItemHandlers;

use SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler;
use SMW\SQLStore\TableBuilder\FieldType;
use SMWDIUri as DIUri;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DIUriHandlerTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

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

	/**
	 * @dataProvider fieldTypeProvider
	 */
	public function testMutableOnFieldTypeFeature( $fieldTypeFeatures, $expected ) {

		$instance = new DIUriHandler(
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

		$provider[] = [
			[ 'http://example.org', '' ]
		];

		$provider[] = [
			[ '', 'http://example.org' ]
		];

		return $provider;
	}

	public function dbKeysExceptionProvider() {

		$provider[] = [
			[ '' ]
		];

		return $provider;
	}

	public function fieldTypeProvider() {

		$provider[] = [
			SMW_FIELDT_NONE,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_serialized' => FieldType::FIELD_TITLE
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_NOCASE,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_serialized' => FieldType::TYPE_CHAR_NOCASE
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_LONG,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_serialized' => FieldType::TYPE_CHAR_LONG
			]
		];

		$provider[] = [
			SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG,
			[
				'o_blob' => FieldType::TYPE_BLOB,
				'o_serialized' => FieldType::TYPE_CHAR_LONG_NOCASE
			]
		];

		return $provider;
	}

}
