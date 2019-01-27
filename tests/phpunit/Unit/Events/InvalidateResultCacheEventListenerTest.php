<?php

namespace SMW\Tests\Events;

use SMW\DIWikiPage;
use SMW\Events\InvalidateResultCacheEventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Events\InvalidateResultCacheEventListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class InvalidateResultCacheEventListenerTest extends \PHPUnit_Framework_TestCase {

	private $cachedQueryResultPrefetcher;
	private $spyLogger;

	protected function setUp() {
		parent::setUp();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->cachedQueryResultPrefetcher = $this->getMockBuilder( '\SMW\Query\Result\CachedQueryResultPrefetcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			InvalidateResultCacheEventListener::class,
			new InvalidateResultCacheEventListener( $this->cachedQueryResultPrefetcher )
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

		$this->cachedQueryResultPrefetcher->expects( $this->once() )
			->method( 'invalidate' );

		$instance = new InvalidateResultCacheEventListener(
			$this->cachedQueryResultPrefetcher
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->execute( $context );
	}

}
