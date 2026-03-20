<?php

namespace SMW\Tests\Unit\Listener\EventListener\EventListeners;

use Onoi\EventDispatcher\DispatchContext;
use PHPUnit\Framework\TestCase;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Query\Cache\ResultCache;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateResultCacheEventListenerTest extends TestCase {

	private $resultCache;
	private $spyLogger;

	protected function setUp(): void {
		parent::setUp();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->resultCache = $this->getMockBuilder( ResultCache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			InvalidateResultCacheEventListener::class,
			new InvalidateResultCacheEventListener( $this->resultCache )
		);
	}

	public function testExecute() {
		$context = DispatchContext::newFromArray(
			[
				'subject' => 'Foo',
				'context' => 'Bar',
				'dependency_list' => []
			]
		);

		$this->resultCache->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new InvalidateResultCacheEventListener(
			$this->resultCache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->execute( $context );
	}

}
