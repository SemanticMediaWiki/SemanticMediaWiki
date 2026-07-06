<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Specials\SpecialConcepts;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\TestEnvironment;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialConceptsTest extends TestCase {

	private $store;
	private $stringValidator;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		// SpecialConcepts now takes the Store via constructor; use the
		// real default store so ListBuilder::buildList can resolve the
		// SortLetter service against an SQLStore (the previous mock-based
		// setup was dead scaffolding once registerObject was removed).
		$this->store = ApplicationFactory::getInstance()->getStore();
		$this->stringValidator = $this->testEnvironment->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			SpecialConcepts::class,
			new SpecialConcepts( $this->store )
		);
	}

	public function testExecute() {
		$expected = 'p class="smw-special-concept-docu plainlinks"';

		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->atLeastOnce() )
			->method( 'addHtml' )
			 ->with( $this->stringContains( $expected ) );

		$query = '';
		$instance = new SpecialConcepts( $this->store );

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SemanticMadiaWiki' )
		);

		$oldOutput = $instance->getOutput();

		$instance->getContext()->setOutput( $outputPage );
		$instance->execute( $query );

		// Context is static avoid any succeeding tests to fail
		$instance->getContext()->setOutput( $oldOutput );
	}

	/**
	 * @depends testExecute
	 */
	public function testGetHtmlForAnEmptySubject() {
		$instance = new SpecialConcepts( $this->store );

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-empty"',
			$instance->getHtml( [], 0, 0 )
		);
	}

	/**
	 * @depends testGetHtmlForAnEmptySubject
	 */
	public function testGetHtmlForSingleSubject() {
		$subject  = WikiPage::newFromText( __METHOD__ );
		$instance = new SpecialConcepts( $this->store );

		$this->stringValidator->assertThatStringContains(
			'div class="smw-special-concept-count"',
			$instance->getHtml( [ $subject ], 1, 0 )
		);
	}

	/**
	 * @dataProvider cursorModeProvider
	 */
	public function testShouldUseCursorMode( ?string $offsetParamValue, bool $expected ): void {
		$this->assertSame(
			$expected,
			SpecialConcepts::shouldUseCursorMode( $offsetParamValue )
		);
	}

	public static function cursorModeProvider(): array {
		return [
			'no offset param at all' => [ null, true ],
			'explicit offset=0' => [ '0', false ],
			'explicit offset=5' => [ '5', false ],
			'empty offset value' => [ '', false ],
			'negative offset' => [ '-1', false ],
			'non-numeric garbage' => [ 'garbage', false ],
		];
	}

}
