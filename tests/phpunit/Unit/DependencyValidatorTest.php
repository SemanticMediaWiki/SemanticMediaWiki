<?php

namespace SMW\Tests;

use SMW\DependencyValidator;
use SMW\DIWikiPage;

/**
 * @covers \SMW\DependencyValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dependencyLinksValidator;
	private $namespaceExaminer;
	private $entityCache;
	private $logger;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksValidator = $this->getMockBuilder( '\SMW\SQLStore\QueryDependency\DependencyLinksValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( '\SMW\NamespaceExaminer' )
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
			DependencyValidator::class,
			new DependencyValidator( $this->namespaceExaminer, $this->dependencyLinksValidator, $this->entityCache )
		);
	}

	public function testHasArchaicDependencies() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'overrideSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				$this->stringContains( 'foo-etag' ) );

		$eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( $this->equalTo( 'InvalidateResultCache' ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->will( $this->returnValue( true ) );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( true ) );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'getCheckedDependencies' )
			->will( $this->returnValue( [] ) );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setETag( 'foo-etag' );

		$instance->setEventDispatcher(
			$eventDispatcher
		);

		$this->assertTrue(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasNoArchaicDependencies() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->never() )
			->method( 'overrideSub' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->will( $this->returnValue( true ) );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $this->equalTo( $subject ) )
			->will( $this->returnValue( false ) );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setETag( 'foo-etag' );

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasNoArchaicDependencies_DisabledNamespace() {

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->never() )
			->method( 'overrideSub' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setETag( 'foo-etag' );

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testMarkTitle() {

		$subject = DIWikiPage::newFromText( 'Foo' );
		$title = $subject->getTitle();

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->markTitle( $title );

		$this->assertTrue(
			$instance->hasLikelyOutdatedDependencies( $title )
		);
	}

	public function testCanKeepParserCache_NoCache() {

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->never() )
			->method( 'fetchSub' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$this->assertTrue(
			$instance->canKeepParserCache( $subject )
		);
	}

	public function testCanKeepParserCache_NoCacheOnFetch() {

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->with( $this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ) )
			->will( $this->returnValue( true ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setETag( 'foo-etag' );

		$this->assertTrue(
			$instance->canKeepParserCache( $subject )
		);
	}

	public function testCanNotKeepParserCache() {

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->will( $this->returnValue( false ) );

		$this->entityCache->expects( $this->once() )
			->method( 'saveSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				$this->stringContains( 'foo-etag' ) );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$instance = new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache
		);

		$instance->setETag( 'foo-etag' );

		$this->assertFalse(
			$instance->canKeepParserCache( $subject )
		);
	}

}
