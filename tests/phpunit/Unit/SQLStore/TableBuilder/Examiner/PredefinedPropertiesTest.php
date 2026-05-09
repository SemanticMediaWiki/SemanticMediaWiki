<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertiesTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PredefinedProperties::class,
			new PredefinedProperties( $this->store )
		);
	}

	public function testCheckOnValidProperty() {
		$row = [
			'smw_id' => 42,
			'smw_iw' => '',
			'smw_proptable_hash' => '',
			'smw_hash' => '',
			'smw_rev' => null,
			'smw_touched' => ''
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getPropertyInterwiki', 'moveSMWPageID', 'getPropertyTableHashes' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getPropertyInterwiki' )
			->willReturn( 'Foo' );

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$capturedReplaceTables = [];
		$capturedReplaceRows = [];
		$capturedReplaceUniqueIndexFields = [];
		$replaceBuilder = $this->createMockReplaceQueryBuilder(
			$capturedReplaceTables,
			$capturedReplaceRows,
			$capturedReplaceUniqueIndexFields
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new PredefinedProperties(
			$store
		);

		$instance->setPredefinedPropertyList( [
			'Foo' => 42
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertSame( [ SQLStore::ID_TABLE ], $capturedReplaceTables );
		$this->assertSame( [ [ 'smw_id' ] ], $capturedReplaceUniqueIndexFields );
	}

	public function testCheckOnValidProperty_NotFixed() {
		$row = [
			'smw_id' => 42,
			'smw_iw' => '',
			'smw_proptable_hash' => '',
			'smw_hash' => '',
			'smw_rev' => null,
			'smw_touched' => ''
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'moveSMWPageID', 'getPropertyInterwiki' ] )
			->getMock();

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)$row ], $capturedWheres );

		$replaceBuilder = $this->createMockReplaceQueryBuilder();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newReplaceQueryBuilder' )
			->willReturn( $replaceBuilder );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new PredefinedProperties(
			$store
		);

		$instance->setPredefinedPropertyList( [
			'Foo' => null
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertContains(
			[
				'smw_title' => 'Foo',
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_subobject' => ''
			],
			$capturedWheres
		);
	}

	public function testCheckOnInvalidProperty() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getPropertyInterwiki', 'moveSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->never() )
			->method( 'getPropertyInterwiki' );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new PredefinedProperties(
			$store
		);

		$instance->setPredefinedPropertyList( [
			'_FOO' => 42
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertStringContainsString(
			'invalid registration',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
