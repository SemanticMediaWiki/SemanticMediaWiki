<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\FauxRequest;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Specials\SpecialAdmin;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialAdmin
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialAdminTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $settings;
	private $hookContainer;
	private $jobFactory;
	private $jobQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		// SpecialAdmin's TaskHandler chain needs an SQLStore-typed surface;
		// resolve the real store and settings here. Transitive collaborators
		// reach back through ApplicationFactory but those are stable
		// services available in the integration test environment.
		$applicationFactory = ApplicationFactory::getInstance();
		$this->store = $applicationFactory->getStore();
		$this->settings = $applicationFactory->getSettings();
		$this->hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$this->jobFactory = $applicationFactory->getJobFactory();
		$this->jobQueue = $applicationFactory->getJobQueue();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
		$settings = $this->createMock( Settings::class );
		$hookContainer = $this->createMock( HookContainer::class );
		$jobFactory = $this->createMock( JobFactory::class );
		$jobQueue = $this->createMock( JobQueue::class );

		$this->assertInstanceOf(
			SpecialAdmin::class,
			new SpecialAdmin( $store, $settings, $hookContainer, $jobFactory, $jobQueue )
		);
	}

	public function testExecuteWithValidUser() {
		$user = new MockSuperUser();
		$this->testEnvironment->overrideUserPermissions( $user, [ 'smw-admin' ] );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$query = '';
		$instance = new SpecialAdmin( $this->store, $this->settings, $this->hookContainer, $this->jobFactory, $this->jobQueue );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->getContext()->setUser( $user );

		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

	public function testActionableMaintenanceRequestWithoutValidTokenDoesNotExecuteTheTask() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->never() )
			->method( 'setup' );

		$instance = $this->newSpecialAdminForMaintenanceRequest(
			$store,
			new FauxRequest( [ 'prep' => 'done' ] )
		);

		$instance->execute( 'updatetables' );
	}

	public function testActionableMaintenanceRequestWithValidPostedTokenStillExecutesTheTask() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'setup' );

		$instance = $this->newSpecialAdminForMaintenanceRequest(
			$store,
			new FauxRequest( [ 'prep' => 'done', 'wpEditToken' => 'valid-token' ], true )
		);

		$instance->execute( 'updatetables' );
	}

	private function newSpecialAdminForMaintenanceRequest( $store, FauxRequest $request ): SpecialAdmin {
		$user = new class() extends MockSuperUser {

			public function getEditToken( $salt = '', $request = null ) {
				return 'valid-token';
			}

			public function matchEditToken( $val, $salt = '', $request = null, $maxage = null ) {
				return $val === 'valid-token';
			}
		};

		$this->testEnvironment->overrideUserPermissions( $user, [ 'smw-admin' ] );

		$instance = new SpecialAdmin( $store, $this->settings, $this->hookContainer, $this->jobFactory, $this->jobQueue );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SemanticMediaWiki' )
		);
		$instance->getContext()->setUser( $user );
		$instance->getContext()->setRequest( $request );

		return $instance;
	}

	public function testExecuteWithInvalidPermissionThrowsException() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->overrideUserPermissions( $user, [] );

		$query = '';
		$instance = new SpecialAdmin( $this->store, $this->settings, $this->hookContainer, $this->jobFactory, $this->jobQueue );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SemanticMadiaWiki' )
		);

		$instance->getContext()->setUser( $user );

		$this->expectException( 'PermissionsError' );
		$instance->execute( $query );
	}

}
