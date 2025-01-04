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
class TitleQuickPermissionsTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $titlePermissions;
	private $namespaceExaminer;
	private $title;
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->titlePermissions = $this->getMockBuilder( '\SMW\MediaWiki\Permission\TitlePermissions' )
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

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TitleQuickPermissions::class,
			new TitleQuickPermissions( $this->namespaceExaminer, $this->titlePermissions )
		);
	}

	public function testProcessOnArchaicDependencies_TitleQuickPermissions() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->titlePermissions->expects( $this->once() )
			->method( 'checkPermissionFor' );

		$this->titlePermissions->expects( $this->once() )
			->method( 'getErrors' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->titlePermissions
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

	public function testProcessOnDisabledNamespace() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$this->titlePermissions->expects( $this->never() )
			->method( 'checkPermissionFor' );

		$instance = new TitleQuickPermissions(
			$this->namespaceExaminer,
			$this->titlePermissions
		);

		$error = '';

		$instance->process( $this->title, $this->user, '', $error );
	}

}
