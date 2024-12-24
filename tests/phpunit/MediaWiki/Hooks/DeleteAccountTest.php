<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\DeleteAccount;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\DeleteAccount
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DeleteAccountTest extends \PHPUnit\Framework\TestCase {

	private $namespaceExaminer;
	private $articleDelete;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->articleDelete = $this->getMockBuilder( '\SMW\MediaWiki\Hooks\ArticleDelete' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DeleteAccount::class,
			new DeleteAccount( $this->namespaceExaminer, $this->articleDelete )
		);
	}

	public function testProcess() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( NS_USER )
			->willReturn( true );

		$this->articleDelete->expects( $this->atLeastOnce() )
			->method( 'process' );

		$instance = new DeleteAccount(
			$this->namespaceExaminer,
			$this->articleDelete
		);

		$this->assertTrue(
			$instance->process( 'Foo' )
		);
	}

	public function testProcess_User() {
		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->with( NS_USER )
			->willReturn( true );

		$this->articleDelete->expects( $this->atLeastOnce() )
			->method( 'process' );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$instance = new DeleteAccount(
			$this->namespaceExaminer,
			$this->articleDelete
		);

		$this->assertTrue(
			$instance->process( $user )
		);
	}

}
