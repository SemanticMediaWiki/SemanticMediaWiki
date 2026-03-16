<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertiesTest extends \PHPUnit\Framework\TestCase {

	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$connection->expects( $this->atLeastOnce() )
			->method( 'replace' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->willReturnCallback( static function ( $table, $vars, $conds ) use ( $row ) {
				if ( is_array( $conds ) && isset( $conds['smw_title'] ) && $conds['smw_title'] === 'Foo'
					&& isset( $conds['smw_namespace'] ) && $conds['smw_namespace'] === SMW_NS_PROPERTY
					&& isset( $conds['smw_subobject'] ) && $conds['smw_subobject'] === '' ) {
					return (object)$row;
				}
				return (object)$row;
			} );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
	}

	public function testCheckOnInvalidProperty() {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getPropertyInterwiki', 'moveSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->never() )
			->method( 'getPropertyInterwiki' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
