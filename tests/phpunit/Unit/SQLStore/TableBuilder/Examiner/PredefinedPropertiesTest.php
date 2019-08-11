<?php

namespace SMW\Tests\SQLStore\TableBuilder\Examiner;

use SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PredefinedPropertiesTest extends \PHPUnit_Framework_TestCase {

	private $spyMessageReporter;
	private $store;

	protected function setUp() {
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
			->will( $this->returnValue( 'Foo' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'replace' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

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

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [
					'smw_title' => 'Foo',
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_subobject' => '' ] ) )
			->will( $this->returnValue( (object)$row ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

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

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$instance = new PredefinedProperties(
			$store
		);

		$instance->setPredefinedPropertyList( [
			'_FOO' => 42
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertContains(
			'invalid registration',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
