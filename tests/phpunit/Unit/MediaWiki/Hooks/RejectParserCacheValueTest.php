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
	private $dependencyValidator;
	private $namespaceExaminer;
	private $logger;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->dependencyValidator = $this->getMockBuilder( '\SMW\DependencyValidator' )
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
			RejectParserCacheValue::class,
			new RejectParserCacheValue( $this->namespaceExaminer, $this->dependencyValidator )
		);
	}

	public function testProcesCanKeepParserCache() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->will( $this->returnValue( true ) );

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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( true ) );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'canKeepParserCache' )
			->will( $this->returnValue( false ) );

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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->will( $this->returnValue( false ) );

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
