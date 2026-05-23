<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
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
			new RejectParserCacheValue( $this->namespaceExaminer, $this->logger )
		);
	}

	public function testProcessOnDisabledNamespace() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$instance = new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->logger
		);

		$this->assertTrue(
			$instance->onRejectParserCacheValue( null, $page, null )
		);
	}

}
