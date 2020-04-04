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
	private $permissionsExaminer;
	private $namespaceExaminer;
	private $title;
	private $user;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->permissionsExaminer = $this->getMockBuilder( '\SMW\MediaWiki\PermissionsExaminer' )
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

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TitleQuickPermissions::class,
			new TitleQuickPermissions( $this->namespaceExaminer, $this->permissionsExaminer )
		);
	}

	public function testProcessOnArchaicDependencies_TitleQuickPermissions() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->permissionsExaminer->expects( $this->once() )
			->method( 'checkPermissionFor' );

		$this->permissionsExaminer->expects( $this->once() )
			->method( 'getErrors' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->permissionsExaminer
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

	public function testProcessOnDisabledNamespace() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$this->permissionsExaminer->expects( $this->never() )
			->method( 'checkPermissionFor' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->permissionsExaminer
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

}
