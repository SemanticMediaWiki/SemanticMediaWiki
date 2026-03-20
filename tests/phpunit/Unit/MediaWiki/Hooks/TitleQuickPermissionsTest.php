<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\TitleQuickPermissions;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\NamespaceExaminer;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleQuickPermissions
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TitleQuickPermissionsTest extends TestCase {

	private $testEnvironment;
	private $titlePermissions;
	private $namespaceExaminer;
	private $title;
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->titlePermissions = $this->getMockBuilder( TitlePermissions::class )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( User::class )
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
