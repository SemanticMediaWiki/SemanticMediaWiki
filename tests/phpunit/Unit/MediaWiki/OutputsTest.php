<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Outputs;

/**
 * @covers \SMW\MediaWiki\Outputs
 * @group semantic-mediawiki
 */
class OutputsTest extends TestCase {

	protected function tearDown(): void {
		Outputs::resetParseDepth();
		parent::tearDown();
	}

	private function newMockParserOutput(): ParserOutput {
		$modules = [];
		$styles = [];

		$po = $this->createMock( ParserOutput::class );

		$po->method( 'addModules' )
			->willReturnCallback( static function ( array $m ) use ( &$modules ) {
				$modules = array_values( array_unique( array_merge( $modules, $m ) ) );
			} );

		$po->method( 'addModuleStyles' )
			->willReturnCallback( static function ( array $s ) use ( &$styles ) {
				$styles = array_values( array_unique( array_merge( $styles, $s ) ) );
			} );

		$po->method( 'getModules' )
			->willReturnCallback( static function () use ( &$modules ) {
				return $modules;
			} );

		$po->method( 'getModuleStyles' )
			->willReturnCallback( static function () use ( &$styles ) {
				return $styles;
			} );

		$po->method( 'addHeadItem' );

		return $po;
	}

	/**
	 * Modules registered in an outer parse must survive a nested parse
	 * that also calls commitToParserOutput().
	 *
	 * Timeline:
	 *   1. Outer parse starts          → onParseStart (depth 1)
	 *   2. {{#ask:}} registers module  → requireResource('smw.tableprinter.datatable')
	 *   3. {{#dpl:}} triggers nested   → onParseStart (depth 2)
	 *   4. Nested commitToParserOutput → modules go to nested PO, buffer preserved
	 *   5. Nested parse ends           → onParseEnd (depth 1)
	 *   6. Outer commitToParserOutput  → modules go to outer PO, buffer cleared
	 *   7. Outer parse ends            → onParseEnd (depth 0), final cleanup
	 */
	public function testModulesSurviveNestedParse(): void {
		$outerPO = $this->newMockParserOutput();
		$nestedPO = $this->newMockParserOutput();

		// Step 1: outer parse starts
		Outputs::onParseStart();

		// Step 2: SMW query registers a module
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		Outputs::requireStyle( 'smw.tableprinter.datatable.styles' );

		// Step 3: DPL triggers a nested parse
		Outputs::onParseStart();

		// Step 4: nested InternalParseBeforeLinks commits to nested PO
		Outputs::commitToParserOutput( $nestedPO );

		// Step 5: nested parse ends
		Outputs::onParseEnd();

		// Step 6: outer InternalParseBeforeLinks commits to outer PO
		Outputs::commitToParserOutput( $outerPO );

		// Step 7: outer parse ends
		Outputs::onParseEnd();

		// The outer ParserOutput must have the datatable module
		$this->assertContains(
			'smw.tableprinter.datatable',
			$outerPO->getModules(),
			'Datatable module must reach the outer ParserOutput'
		);

		$this->assertContains(
			'smw.tableprinter.datatable.styles',
			$outerPO->getModuleStyles(),
			'Datatable styles must reach the outer ParserOutput'
		);
	}

	/**
	 * Modules from one page must not leak into a subsequent page parsed
	 * in the same process (batch/job scenario).
	 */
	public function testModulesDoNotLeakAcrossPages(): void {
		$pageAPO = $this->newMockParserOutput();
		$pageBPO = $this->newMockParserOutput();

		// Parse page A (has datatable)
		Outputs::onParseStart();
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		Outputs::commitToParserOutput( $pageAPO );
		Outputs::onParseEnd();

		// Parse page B (plain text, no SMW modules registered)
		Outputs::onParseStart();
		Outputs::commitToParserOutput( $pageBPO );
		Outputs::onParseEnd();

		$this->assertContains(
			'smw.tableprinter.datatable',
			$pageAPO->getModules(),
			'Page A must have the datatable module'
		);

		$this->assertNotContains(
			'smw.tableprinter.datatable',
			$pageBPO->getModules(),
			'Page B must NOT inherit page A\'s datatable module'
		);
	}

	/**
	 * Both scenarios combined: page A has a datatable + nested DPL parse,
	 * page B is plain. Modules must survive the nested parse on page A
	 * but must not leak to page B.
	 */
	public function testNestedParseThenSecondPage(): void {
		$pageAPO = $this->newMockParserOutput();
		$nestedPO = $this->newMockParserOutput();
		$pageBPO = $this->newMockParserOutput();

		// --- Page A with nested parse ---
		Outputs::onParseStart();
		Outputs::requireResource( 'smw.tableprinter.datatable' );

		// Nested parse (DPL)
		Outputs::onParseStart();
		Outputs::commitToParserOutput( $nestedPO );
		Outputs::onParseEnd();

		// Outer commit for page A
		Outputs::commitToParserOutput( $pageAPO );
		Outputs::onParseEnd();

		// --- Page B (plain) ---
		Outputs::onParseStart();
		Outputs::commitToParserOutput( $pageBPO );
		Outputs::onParseEnd();

		$this->assertContains(
			'smw.tableprinter.datatable',
			$pageAPO->getModules(),
			'Page A must have the datatable module after nested parse'
		);

		$this->assertNotContains(
			'smw.tableprinter.datatable',
			$pageBPO->getModules(),
			'Page B must NOT inherit page A\'s modules'
		);
	}

	/**
	 * commitToParserOutput outside of any parse (depth 0) must still
	 * clear the buffers. This is the path used by special pages that
	 * call commitToParser() without going through Parser::parse().
	 */
	public function testCommitOutsideParseClears(): void {
		$po1 = $this->newMockParserOutput();
		$po2 = $this->newMockParserOutput();

		Outputs::requireResource( 'ext.smw.ask' );
		Outputs::commitToParserOutput( $po1 );

		// Second commit should see empty buffers
		Outputs::commitToParserOutput( $po2 );

		$this->assertContains( 'ext.smw.ask', $po1->getModules() );
		$this->assertNotContains( 'ext.smw.ask', $po2->getModules() );
	}
}
