<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialAdmin;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\Utils\Mock\MockSuperUser;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialAdmin
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SpecialAdminTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\SpecialAdmin',
			new SpecialAdmin()
		);
	}

	public function testExecuteWithValidUser() {

		$user = new MockSuperUser();
		$this->testEnvironment->overrideUserPermissions( $user, [ 'smw-admin' ] );

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' );

		$query = '';
		$instance = new SpecialAdmin();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->getContext()->setUser( $user );

		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

	public function testExecuteWithInvalidPermissionThrowsException() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->overrideUserPermissions( $user, [] );

		$query = '';
		$instance = new SpecialAdmin();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SemanticMadiaWiki' )
		);

		$instance->getContext()->setUser( $user );

		$this->expectException( 'PermissionsError' );
		$instance->execute( $query );
	}

}
