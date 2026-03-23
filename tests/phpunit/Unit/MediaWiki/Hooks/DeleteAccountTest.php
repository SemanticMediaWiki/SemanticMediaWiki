<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\Hooks\DeleteAccount;
use SMW\NamespaceExaminer;

/**
 * @covers \SMW\MediaWiki\Hooks\DeleteAccount
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DeleteAccountTest extends TestCase {

	private $namespaceExaminer;
	private $articleDelete;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->articleDelete = $this->getMockBuilder( ArticleDelete::class )
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

		$user = $this->getMockBuilder( User::class )
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
