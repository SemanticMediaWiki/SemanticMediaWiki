<?php

namespace SMW\Tests\Listener\EventListener\EventListeners;

use SMW\DIWikiPage;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateEntityCacheEventListenerTest extends \PHPUnit_Framework_TestCase {

	private $entityCache;
	private $spyLogger;

	protected function setUp() : void {
		parent::setUp();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			InvalidateEntityCacheEventListener::class,
			new InvalidateEntityCacheEventListener( $this->entityCache )
		);
	}

	public function testExecute_OnSubject() {

		$context = DispatchContext::newFromArray(
			[
				'subject' => DIWikiPage::newFromText( __METHOD__ ),
				'context' => 'Bar'
			]
		);

		$this->entityCache->expects( $this->once() )
			->method( 'invalidate' );

		$instance = new InvalidateEntityCacheEventListener(
			$this->entityCache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->execute( $context );
	}

	public function testExecute_OnTitle() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$context = DispatchContext::newFromArray(
			[
				'title' => $title,
				'context' => 'Bar'
			]
		);

		$this->entityCache->expects( $this->once() )
			->method( 'invalidate' );

		$instance = new InvalidateEntityCacheEventListener(
			$this->entityCache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->execute( $context );
	}

}
