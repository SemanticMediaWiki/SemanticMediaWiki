<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Hooks\TitleMoveComplete;
use SMW\Settings;
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

	private $applicationFactory;
	private $user;

	protected function setUp() {
		parent::setUp();

		$this->user = new MockSuperUser();
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

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

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->never() )
			->method( 'changeTitle' );

		$this->applicationFactory->registerObject( 'Settings', Settings::newFromArray( array(
			'smwgCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		) ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$this->user,
			0,
			0
		);

		$this->assertTrue(
			$instance->process()
		);
	}

	public function testDeleteSubjectForNotSupportedSemanticNamespace() {

		$oldTitle = \Title::newFromText( 'Old' );
		$newTitle = \Title::newFromText( 'New', NS_HELP );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'deleteSubject' )
			->with(
				$this->equalTo( $oldTitle ) );

		$this->applicationFactory->registerObject( 'Settings', Settings::newFromArray( array(
			'smwgCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true, NS_HELP => false )
		) ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			$this->user,
			0,
			0
		);

		$this->assertTrue(
			$instance->process()
		);
	}

}
