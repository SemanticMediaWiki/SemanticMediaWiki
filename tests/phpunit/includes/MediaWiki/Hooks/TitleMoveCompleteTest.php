<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\Mock\MockSuperUser;

use SMW\MediaWiki\Hooks\TitleMoveComplete;
use SMW\ApplicationFactory;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\Hooks\TitleMoveComplete
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TitleMoveCompleteTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

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
			new MockSuperUser(),
			0,
			0
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Hooks\TitleMoveComplete',
			$instance
		);
	}

	public function testProcess() {

		$oldTitle = MockTitle::buildMock( 'old' );
		$newTitle =	MockTitle::buildMock( 'new' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'changeTitle' )
			->with(
				$this->equalTo( $oldTitle ),
				$this->equalTo( $newTitle ),
				$this->anything(),
				$this->anything() );

		$this->applicationFactory->registerObject( 'Settings', Settings::newFromArray( array(
			'smwgCacheType'             => 'hash',
			'smwgAutoRefreshOnPageMove' => true,
		) ) );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new TitleMoveComplete(
			$oldTitle,
			$newTitle,
			new MockSuperUser(),
			0,
			0
		);

		$this->assertTrue( $instance->process() );
	}

}
