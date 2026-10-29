<?php

namespace SMW\Tests\Unit;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
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

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		ConvertibleTimestamp::setFakeTime( false );
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): DependencyValidator {
		return new DependencyValidator(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
			$this->entityCache,
			3600,
			$this->eventDispatcher
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

		$this->eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( 'InvalidateResultCache' );

		$subject = WikiPage::newFromText( 'Foo' );

		$instance = $this->newInstance();

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
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $title, '20260601120001' )
		);
	}

	public function testKeepsOutputRenderedInTheSameSecondOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $title, '20260601120000' )
		);
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $title, '20260601115959' )
		);
	}

	public function testKeepsRejectingOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );
		$this->newInstance()->canKeepParserCache( $title, '20260601115959' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $title, '20260601115959' )
		);
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFoundAgain() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );
		$this->findOutdatedDependenciesAt( $title, '20260601130000' );

		$this->assertFalse(
			$this->newInstance()->canKeepParserCache( $title, '20260601123000' )
		);
	}

	public function testKeepsOutputWhenNoOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->checkDependenciesAt( $title, '20260601120000', outdated: false );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( $title, '20260601115959' )
		);
	}

	public function testKeepsOutputOfOtherPages() {
		$this->findOutdatedDependenciesAt( Title::makeTitle( NS_MAIN, 'Foo' ), '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->canKeepParserCache( Title::makeTitle( NS_HELP, 'Foo' ), '20260601115959' )
		);
	}

	private function findOutdatedDependenciesAt( Title $title, string $time ): void {
		$this->checkDependenciesAt( $title, $time, outdated: true );
	}

	private function checkDependenciesAt( Title $title, string $time, bool $outdated ): void {
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'canCheckDependencies' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'hasArchaicDependencies' )->willReturn( $outdated );

		ConvertibleTimestamp::setFakeTime( $time );
		$this->newInstance()->hasArchaicDependencies( WikiPage::newFromTitle( $title ) );
	}

}
