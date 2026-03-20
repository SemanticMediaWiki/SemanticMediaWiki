<?php

namespace SMW\Tests\Unit\Listener\EventListener\EventListeners;

use Onoi\EventDispatcher\DispatchContext;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Property\SpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class InvalidatePropertySpecificationLookupCacheEventListenerTest extends TestCase {

	private $specificationLookup;
	private $spyLogger;

	protected function setUp(): void {
		parent::setUp();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->specificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			InvalidatePropertySpecificationLookupCacheEventListener::class,
			new InvalidatePropertySpecificationLookupCacheEventListener( $this->specificationLookup )
		);
	}

	public function testExecute_OnSubject() {
		$context = DispatchContext::newFromArray(
			[
				'subject' => WikiPage::newFromText( __METHOD__ ),
				'context' => 'Bar'
			]
		);

		$this->specificationLookup->expects( $this->once() )
			->method( 'invalidateCache' );

		$instance = new InvalidatePropertySpecificationLookupCacheEventListener(
			$this->specificationLookup
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->execute( $context );
	}

}
