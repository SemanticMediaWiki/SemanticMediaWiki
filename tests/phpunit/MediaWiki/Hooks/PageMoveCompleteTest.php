<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\PageMoveComplete;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\PageMoveComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PageMoveCompleteTest extends \PHPUnit\Framework\TestCase {

	private $user;
	private $testEnvironment;
	private $namespaceExaminer;
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->user = new MockSuperUser();

		$settings = [
			'smwgMainCacheType' => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, NS_HELP => false ]
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageMoveComplete::class,
			new PageMoveComplete( $this->namespaceExaminer )
		);
	}

	public function testChangeSubjectForSupportedSemanticNamespace() {
		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'changeTitle' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new PageMoveComplete(
			$this->namespaceExaminer
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process( $oldTitle, $newTitle, $this->user, 0, 0 )
		);
	}

	public function testDeleteSubjectForNotSupportedSemanticNamespace() {
		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New', NS_HELP );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'deleteSubject' )
			->with( $oldTitle );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new PageMoveComplete(
			$this->namespaceExaminer
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process( $oldTitle, $newTitle, $this->user, 0, 0 )
		);
	}

}
