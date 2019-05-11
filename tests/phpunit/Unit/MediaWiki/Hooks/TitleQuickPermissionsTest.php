<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\TitleQuickPermissions;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleQuickPermissions
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TitleQuickPermissionsTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $permissionManager;
	private $namespaceExaminer;
	private $title;
	private $user;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->permissionManager = $this->getMockBuilder( '\SMW\MediaWiki\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TitleQuickPermissions::class,
			new TitleQuickPermissions( $this->namespaceExaminer, $this->permissionManager )
		);
	}

	public function testProcessOnArchaicDependencies_TitleQuickPermissions() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->permissionManager->expects( $this->once() )
			->method( 'checkQuickPermission' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->permissionManager
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

	public function testProcessOnDisabledNamespace() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$this->permissionManager->expects( $this->never() )
			->method( 'checkQuickPermission' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->permissionManager
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

}
