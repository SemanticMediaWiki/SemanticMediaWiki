<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage;
use SMW\DependencyValidator;
use SMW\EntityCache;
use SMW\EventDispatcher\EventDispatcher;
use SMW\NamespaceExaminer;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DependencyValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyValidatorTest extends TestCase {

	private $testEnvironment;
	private $dependencyLinksValidator;
	private $namespaceExaminer;
	private $entityCache;
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksValidator = $this->getMockBuilder( DependencyLinksValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = $this->getMockBuilder( LoggerInterface::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance( string $eTag = '', int $cacheTTL = 3600 ): DependencyValidator {
		return new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache,
			$eTag,
			$cacheTTL
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DependencyValidator::class,
			$this->newInstance()
		);
	}

	public function testHasArchaicDependencies() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'overrideSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				'_smw_dirty_',
				'1',
				$this->anything()
			);

		$eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( 'InvalidateResultCache' );

		$subject = WikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->willReturn( true );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $subject )
			->willReturn( true );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'getCheckedDependencies' )
			->willReturn( [] );

		$instance = $this->newInstance( 'foo-etag' );

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
			->willReturn( true );

		$this->entityCache->expects( $this->never() )
			->method( 'overrideSub' );

		$subject = WikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->willReturn( true );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $subject )
			->willReturn( false );

		$instance = $this->newInstance( 'foo-etag' );

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasNoArchaicDependencies_DisabledNamespace() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$this->entityCache->expects( $this->never() )
			->method( 'overrideSub' );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance( 'foo-etag' );

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testMarkTitle() {
		$subject = WikiPage::newFromText( 'Foo' );
		$title = $subject->getTitle();

		$instance = $this->newInstance();

		$instance->markTitle( $title );

		$this->assertTrue(
			$instance->hasLikelyOutdatedDependencies( $title )
		);
	}

	public function testCanKeepParserCache_NoCache() {
		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( false );

		$this->entityCache->expects( $this->never() )
			->method( 'fetchSub' );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance();

		$this->assertTrue(
			$instance->canKeepParserCache( $subject )
		);
	}

	public function testCanKeepParserCache_NoCacheOnFetch() {
		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->with( $this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ) )
			->willReturn( true );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance( 'foo-etag' );

		$this->assertTrue(
			$instance->canKeepParserCache( $subject )
		);
	}

	public function testCanNotKeepParserCache() {
		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->willReturn( false );

		$this->entityCache->expects( $this->once() )
			->method( 'saveSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				$this->stringContains( 'foo-etag' ) );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance( 'foo-etag' );

		$this->assertFalse(
			$instance->canKeepParserCache( $subject )
		);
	}

	public function testCanKeepParserCache_RejectsAfterDirtyMarker() {
		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetchSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				$this->stringContains( 'foo-etag' )
			)
			->willReturn( false );

		$this->entityCache->expects( $this->once() )
			->method( 'saveSub' )
			->with(
				$this->stringContains( 'smw:entity:2623cc3534dff8ce37b7b27e1b009a96' ),
				$this->stringContains( 'foo-etag' ),
				$this->anything(),
				$this->anything()
			);

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance( 'foo-etag' );

		$this->assertFalse(
			$instance->canKeepParserCache( $subject )
		);
	}

}
