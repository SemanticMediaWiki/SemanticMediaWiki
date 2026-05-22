<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\User\Options\UserOptionsLookup;
use PHPUnit\Framework\TestCase;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\MediaWiki\Hooks\OutputPageParserOutput;
use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\Hooks\OutputPageParserOutput
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutputTest extends TestCase {

	private $testEnvironment;
	private $applicationFactory;
	private $namespaceExaminer;
	private $userOptionsLookup;
	private FactboxText $factboxText;
	private FactboxFactory $factboxFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->testEnvironment->withConfiguration(
			[
				'smwgShowFactbox'   => 'nonempty',
				'smwgMainCacheType' => 'hash'
			]
		);

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );

		$this->factboxText = $this->applicationFactory->getFactboxText();

		$this->factboxFactory = $this->getMockBuilder( FactboxFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OutputPageParserOutput::class,
			new OutputPageParserOutput( $this->namespaceExaminer, $this->factboxText, $this->factboxFactory, $this->userOptionsLookup )
		);
	}

	public function testProcessReturnsEarlyForSpecialPage() {
		$title = MockTitle::buildMock( __METHOD__ . 'mock-specialpage' );

		$title->expects( $this->atLeastOnce() )
			->method( 'isSpecialPage' )
			->willReturn( true );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->never() )
			->method( 'isSemanticEnabled' );

		$instance = new OutputPageParserOutput(
			$this->namespaceExaminer,
			$this->factboxText,
			$this->factboxFactory,
			$this->userOptionsLookup
		);

		$parserOutput = new ParserOutput();
		$instance->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertFalse( $this->factboxText->hasText() );
	}

	public function testProcessReturnsEarlyForDisabledNamespace() {
		$title = MockTitle::buildMock( __METHOD__ . 'title-ns-disabled' );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'getTitle' )
			->willReturn( $title );

		$this->namespaceExaminer->expects( $this->once() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$instance = new OutputPageParserOutput(
			$this->namespaceExaminer,
			$this->factboxText,
			$this->factboxFactory,
			$this->userOptionsLookup
		);

		$parserOutput = new ParserOutput();
		$instance->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertFalse( $this->factboxText->hasText() );
	}

}
