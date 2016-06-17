<?php

namespace SMW\Test;

use FauxRequest;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SMWAdmin;
use User;

/**
 * @covers \SMWAdmin
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialSMWAdminTest extends SpecialPageTestCase {

	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() {
		parent::tearDown();
		$this->testEnvironment->tearDown();
	}

	public function getClass() {
		return '\SMWAdmin';
	}

	protected function getInstance() {
		return new SMWAdmin();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	public function testExecuteWithMissingPermissionThrowsException() {

		$this->setExpectedException( 'PermissionsError' );
		$this->execute( '', null, new User );
	}

	public function testExecute() {

		$this->execute( '', null, new MockSuperUser );
		$this->assertInternalType( 'string', $this->getText() );
	}

	/**
	 * @depends testExecute
	 */
	public function testExecuteOnActionListSettings() {

		$this->execute( '', new FauxRequest( array( 'action' => 'listsettings' ) ), new MockSuperUser );
		$this->assertInternalType( 'string', $this->getText() );
	}

	/**
	 * @depends testExecute
	 */
	public function testExecuteOnIdLookup() {

		$selectRow = new \stdClass;
		$selectRow->smw_title = 'Queey';

		$database = $this->getMockBuilder( 'SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$database->expects( $this->at( 1 ) )
			->method( 'selectRow' )
			->with( $this->equalTo( \SMWSql3SmwIds::TABLE_NAME ) )
			->will( $this->returnValue( $selectRow ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$request = new FauxRequest( array(
			'action' => 'idlookup',
			'id' => '9999'
		) );

		$this->setStore( $store );
		$this->execute( '', $request, new MockSuperUser() );

		$this->assertInternalType(
			'string',
			$this->getText()
		);
	}

	public function testExecuteOnIdDispose() {

		$database = $this->getMockBuilder( 'SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$database->expects( $this->at( 0 ) )
			->method( 'delete' );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$request = new FauxRequest( array(
			'action' => 'iddispose',
			'id' => '9999'
		) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->setStore( $store );
		$this->execute( '', $request, new MockSuperUser() );

		$this->assertInternalType(
			'string',
			$this->getText()
		);
	}

}
