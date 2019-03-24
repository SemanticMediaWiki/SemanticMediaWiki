<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RejectParserCacheValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dependencyLinksValidator;
	private $entityCache;
	private $logger;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksValidator = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			new RejectParserCacheValue( $this->dependencyLinksValidator, $this->entityCache )
		);
	}

	public function testProcessOnArchaicDependencies_RejectParserCacheValue() {

		$this->entityCache->expects( $this->once() )
			->method( 'overrideSub' )
			->with(
				$this->stringContains( 'smw:entity:316ab10349fcb05c07001bdeb1a490c4' ),
				$this->stringContains( 'foo-etag' ) );

		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateResultCache' ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( true ) );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'getCheckedDependencies' )
			->will( $this->returnValue( [] ) );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setEventDispatcher(
			$eventDispatcher
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertFalse(
			$instance->process( $subject->getTitle(), 'foo-etag' )
		);
	}

	public function testProcessOnArchaicDependencies_RejectParserCacheValueOnDifferentEtag() {

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->with( $this->stringContains( 'smw:entity:316ab10349fcb05c07001bdeb1a490c4' ) )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->once() )
			->method( 'saveSub' )
			->with(
				$this->stringContains( 'smw:entity:316ab10349fcb05c07001bdeb1a490c4' ),
				$this->stringContains( 'foo-etag-2' ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( false ) );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertFalse(
			$instance->process( $subject->getTitle(), 'foo-etag-2' )
		);
	}

	public function testProcessOnArchaicDependencies_KeepParserCacheValueOnUnknownDependency() {

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->never() )
			->method( 'fetchSub' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( false ) );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertTrue(
			$instance->process( $subject->getTitle(), 'foo-etag-2' )
		);
	}

	public function testProcessOnDisabledDependenciesCheck() {

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->will( $this->returnValue( false ) );

		$instance = new RejectParserCacheValue(
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertTrue(
			$instance->process( $subject->getTitle(), 'foo-etag-2' )
		);
	}

}
