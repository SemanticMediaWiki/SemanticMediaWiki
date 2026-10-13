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
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\Timestamp\ConvertibleTimestamp;

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
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyLinksValidator = $this->getMockBuilder( DependencyLinksValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->entityCache = new EntityCache( new HashBagOStuff() );

		$this->logger = $this->getMockBuilder( LoggerInterface::class )
			->disableOriginalConstructor()
			->getMock();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		ConvertibleTimestamp::setFakeTime( false );
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance( int $cacheTTL = 3600, ?EventDispatcher $eventDispatcher = null ): DependencyValidator {
		return new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache,
			$cacheTTL,
			$eventDispatcher ?? $this->eventDispatcher
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

		$eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( 'InvalidateResultCache' );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance( 3600, $eventDispatcher );

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

		$this->assertTrue(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasNoArchaicDependencies() {
		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$subject = WikiPage::newFromText( 'Foo' );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'canCheckDependencies' )
			->willReturn( true );

		$this->dependencyLinksValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->with( $subject )
			->willReturn( false );

		$instance = $this->newInstance();

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasNoArchaicDependencies_DisabledNamespace() {
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );
		$this->dependencyLinksValidator->method( 'canCheckDependencies' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'hasArchaicDependencies' )->willReturn( true );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance();

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

	public function testHasLikelyOutdatedDependencies_RemainsTrueAcrossRepeatedChecks() {
		// #7044: markTitle/hasLikelyOutdatedDependencies intentionally has no
		// reset or expiry (by design, see #6882's "Out of scope" note). This
		// means a client that keeps reloading a title with outdated
		// dependencies will keep observing `true` here on every repeated
		// view/request until the underlying update job runs and a fresh view
		// no longer calls markTitle(). Bounding the number of client reloads
		// is therefore the client's responsibility (ext.smw.util.purge.js).
		$subject = WikiPage::newFromText( 'Foo' );
		$title = $subject->getTitle();

		$instance = $this->newInstance();
		$instance->markTitle( $title );

		$this->assertTrue( $instance->hasLikelyOutdatedDependencies( $title ) );
		$this->assertTrue( $instance->hasLikelyOutdatedDependencies( $title ) );
		$this->assertTrue( $instance->hasLikelyOutdatedDependencies( $title ) );
	}

	public function testKeepsOutputRenderedAfterOutdatedDependenciesWereFound() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->findOutdatedDependenciesAt( $subject, '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $subject, '20260601120001' )
		);
	}

	public function testKeepsOutputRenderedInTheSameSecondOutdatedDependenciesWereFound() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->findOutdatedDependenciesAt( $subject, '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $subject, '20260601120000' )
		);
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->findOutdatedDependenciesAt( $subject, '20260601120000' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $subject, '20260601115959' )
		);
	}

	public function testKeepsRejectingOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->findOutdatedDependenciesAt( $subject, '20260601120000' );
		$this->newInstance()->canKeepParserCache( $subject, '20260601115959' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $subject, '20260601115959' )
		);
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFoundAgain() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->findOutdatedDependenciesAt( $subject, '20260601120000' );
		$this->findOutdatedDependenciesAt( $subject, '20260601130000' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $subject, '20260601123000' )
		);
	}

	public function testKeepsOutputWhenNoOutdatedDependenciesWereFound() {
		$subject = WikiPage::newFromText( 'Foo' );
		$this->checkDependenciesAt( $subject, '20260601120000', outdated: false );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $subject, '20260601115959' )
		);
	}

	public function testKeepsOutputOfOtherPages() {
		$this->findOutdatedDependenciesAt( WikiPage::newFromText( 'Foo' ), '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( WikiPage::newFromText( 'Bar' ), '20260601115959' )
		);
	}

	private function findOutdatedDependenciesAt( WikiPage $subject, string $time ): void {
		$this->checkDependenciesAt( $subject, $time, outdated: true );
	}

	private function checkDependenciesAt( WikiPage $subject, string $time, bool $outdated ): void {
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'canCheckDependencies' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'hasArchaicDependencies' )->willReturn( $outdated );

		ConvertibleTimestamp::setFakeTime( $time );
		$this->newInstance()->hasArchaicDependencies( $subject );
	}

}
