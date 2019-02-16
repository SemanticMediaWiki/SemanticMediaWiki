<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\TitleMoveComplete;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockSuperUser;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleMoveComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TitleMoveCompleteTest extends \PHPUnit_Framework_TestCase {

	private $user;
	private $testEnvironment;
	private $eventDispatcher;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->user = new MockSuperUser();

		$settings = [
			'smwgMainCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, NS_HELP => false ]
		];

		$this->testEnvironment->withConfiguration(
			$settings
		);

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$oldTitle = MockTitle::buildMock( 'old' );
		$newTitle =	MockTitle::buildMock( 'new' );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$this->user,
			0,
			0
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\TitleMoveComplete',
			$instance
		);
	}

	public function testChangeSubjectForSupportedSemanticNamespace() {

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateEntityCache' ) );

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'changeTitle' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$this->user,
			0,
			0
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process()
		);
	}

	public function testDeleteSubjectForNotSupportedSemanticNamespace() {

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateEntityCache' ) );

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New', NS_HELP );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'deleteSubject' )
			->with(
				$this->equalTo( $oldTitle ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$this->user,
			0,
			0
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertTrue(
			$instance->process()
		);
	}

}
