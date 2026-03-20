<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DependencyValidator;
use SMW\EntityCache;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\NamespaceExaminer;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RejectParserCacheValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValueTest extends TestCase {

	private $testEnvironment;
	private $dependencyValidator;
	private $namespaceExaminer;

	private $entityCache;
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyValidator = $this->getMockBuilder( DependencyValidator::class )
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

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			new RejectParserCacheValue( $this->namespaceExaminer, $this->dependencyValidator )
		);
	}

	public function testProcesCanKeepParserCache() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->willReturn( true );

		$instance = new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertTrue(
			$instance->process( $page )
		);
	}

	public function testProcesCanNOTKeepParserCache() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->willReturn( false );

		$instance = new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertFalse(
			$instance->process( $page )
		);
	}

	public function testProcessOnDisabledNamespace() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$this->dependencyValidator->expects( $this->never() )
			->method( 'canKeepParserCache' );

		$instance = new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setLogger(
			$this->logger
		);

		$this->assertTrue(
			$instance->process( $page )
		);
	}

}
