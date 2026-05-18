<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\Hooks\PageMoveComplete;
use SMW\NamespaceExaminer;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;

/**
 * @covers \SMW\MediaWiki\Hooks\PageMoveComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PageMoveCompleteTest extends TestCase {

	private $user;
	private $testEnvironment;
	private $namespaceExaminer;
	private $eventDispatcher;
	private $store;

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

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PageMoveComplete::class,
			new PageMoveComplete( $this->namespaceExaminer, $this->store )
		);
	}

	public function testChangeSubjectForSupportedSemanticNamespace() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$oldTitle = $titleFactory->newFromText( 'Old' );
		$newTitle = $titleFactory->newFromText( 'New' );

		$this->store->expects( $this->never() )
			->method( 'changeTitle' );

		$instance = new PageMoveComplete(
			$this->namespaceExaminer,
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process( $oldTitle, $newTitle, $this->user, 0, 0 )
		);
	}

	public function testDeleteSubjectForNotSupportedSemanticNamespace() {
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$oldTitle = $titleFactory->newFromText( 'Old' );
		$newTitle = $titleFactory->newFromText( 'New', NS_HELP );

		$this->store->expects( $this->once() )
			->method( 'deleteSubject' )
			->with( $oldTitle );

		$instance = new PageMoveComplete(
			$this->namespaceExaminer,
			$this->store
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process( $oldTitle, $newTitle, $this->user, 0, 0 )
		);
	}

}
