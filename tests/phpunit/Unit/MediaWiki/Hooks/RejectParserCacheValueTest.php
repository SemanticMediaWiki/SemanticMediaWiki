<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DependencyValidator;
use SMW\DependencyValidatorFactory;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\NamespaceExaminer;
use SMW\Tests\TestEnvironment;
use WikiPage;

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
	private $namespaceExaminer;
	private $logger;
	private $dependencyValidator;
	private $dependencyValidatorFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->dependencyValidator = $this->createMock( DependencyValidator::class );

		$this->dependencyValidatorFactory = $this->createMock( DependencyValidatorFactory::class );
		$this->dependencyValidatorFactory->method( 'newFor' )->willReturn( $this->dependencyValidator );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): RejectParserCacheValue {
		return new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->logger,
			$this->dependencyValidatorFactory
		);
	}

	private function newPage( Title $title ): WikiPage {
		$page = $this->createMock( WikiPage::class );
		$page->method( 'getTitle' )->willReturn( $title );
		return $page;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			$this->newInstance()
		);
	}

	public function testProcessOnDisabledNamespace() {
		$title = $this->createMock( Title::class );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$this->dependencyValidatorFactory->expects( $this->never() )->method( 'newFor' );

		$this->assertTrue(
			$this->newInstance()->onRejectParserCacheValue( null, $this->newPage( $title ), null )
		);
	}

	public function testProcessCanKeepParserCache() {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->willReturn( true );

		$this->assertTrue(
			$this->newInstance()->onRejectParserCacheValue(
				null,
				$this->newPage( $title ),
				$this->createMock( ParserOptions::class )
			)
		);
	}

	public function testProcessCanNOTKeepParserCache() {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )->willReturn( NS_MAIN );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->willReturn( false );

		$this->assertFalse(
			$this->newInstance()->onRejectParserCacheValue(
				null,
				$this->newPage( $title ),
				$this->createMock( ParserOptions::class )
			)
		);
	}

}
