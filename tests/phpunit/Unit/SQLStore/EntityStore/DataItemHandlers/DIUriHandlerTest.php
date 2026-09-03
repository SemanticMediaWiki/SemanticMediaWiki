<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore\DataItemHandlers;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Uri;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler;
use SMW\SQLStore\EntityStore\Exception\DataItemHandlerException;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\FieldType;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DIUriHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.0
 *
 * @author mwjames
 */
class DIUriHandlerTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
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

	public function testPercentEncodingSurvivesGetInsertValues() {
		$uri = new Uri( 'http', 'example.org/a%2Fb', '', '' );

		$instance = new DIUriHandler( $this->store );

		$this->assertSame(
			'http://example.org/a%2Fb',
			$instance->getInsertValues( $uri )['o_serialized']
		);
	}

	public function testPercentEncodingSurvivesGetWhereConds() {
		$uri = new Uri( 'http', 'example.org/a%2Fb', '', '' );

		$instance = new DIUriHandler( $this->store );

		$this->assertSame(
			'http://example.org/a%2Fb',
			$instance->getWhereConds( $uri )['o_serialized']
		);
	}

	public function testLegacyWhereCondsMatchThePreRebuildStorageForm(): void {
		$uri = new Uri( 'http', 'example.org/a%7Bb%7D', '', '' );

		$instance = new DIUriHandler( $this->store );

		$this->assertSame(
			'http://example.org/a{b}',
			$instance->getLegacyWhereConds( $uri )['o_serialized']
		);
	}

	/**
	 * @dataProvider uriWithoutLegacyFormProvider
	 */
	public function testReturnsNoLegacyWhereConds( string $hierpart ): void {
		$uri = new Uri( 'http', $hierpart, '', '' );

		$instance = new DIUriHandler( $this->store );

		$this->assertSame(
			[],
			$instance->getLegacyWhereConds( $uri )
		);
	}

	public static function uriWithoutLegacyFormProvider(): iterable {
		yield 'storage form unchanged' => [ 'example.org/a/b' ];
		yield 'old form is also the current form of a different URI' => [ 'example.org/a%2Fb' ];
		yield 'old form contains NUL' => [ 'example.org/a%00b%7B' ];
		yield 'old form is not valid UTF-8' => [ 'example.org/a%C3%28%7B' ];
		yield 'difference lies beyond the indexed length' => [ 'example.org/' . str_repeat( 'a', 300 ) . '%7B' ];
	}

	public function testMutableMethodAccess() {
		$uri = new Uri( 'http', 'example.org', '', '' );

		$instance = new DIUriHandler(
			$this->store
		);

		$this->assertIsArray(

			$instance->getWhereConds( $uri )
		);

		$this->assertIsArray(

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

		$this->expectException( DataItemHandlerException::class );
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
