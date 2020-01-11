<?php

namespace SMW\Tests\Events;

use SMW\DIWikiPage;
use SMW\Events\InvalidatePropertySpecificationLookupCacheEventListener;
use Onoi\EventDispatcher\DispatchContext;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Events\InvalidatePropertySpecificationLookupCacheEventListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class InvalidatePropertySpecificationLookupCacheEventListenerTest extends \PHPUnit_Framework_TestCase {

	private $specificationLookup;
	private $spyLogger;

	protected function setUp() {
		parent::setUp();

		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->specificationLookup = $this->getMockBuilder( '\SMW\Property\SpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
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
				'subject' => DIWikiPage::newFromText( __METHOD__ ),
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
