<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Outputs;

/**
 * @covers \SMW\MediaWiki\Outputs
 * @group semantic-mediawiki
 */
class OutputsTest extends TestCase {

	protected function tearDown(): void {
		Outputs::reset();
		parent::tearDown();
	}

	/**
	 * A ParserOutput mock that records the modules and module styles added to
	 * it, deduplicating exactly like the real ParserOutput::addModules().
	 */
	private function newMockParserOutput(): ParserOutput {
		$modules = [];
		$styles = [];
		$headItems = [];

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

		// Records head items keyed by their id, matching ParserOutput's dedup.
		$po->method( 'addHeadItem' )
			->willReturnCallback( static function ( $content, $key = false ) use ( &$headItems ) {
				if ( $key === false ) {
					$headItems[] = $content;
				} else {
					$headItems[$key] = $content;
				}
			} );

		$po->method( 'getHeadItems' )
			->willReturnCallback( static function () use ( &$headItems ) {
				return $headItems;
			} );

		return $po;
	}

	/**
	 * Build a Parser mock whose lock state tracks the by-reference `$locked`
	 * flag, so a test can simulate a parser leaving `Parser::parse()` (normally
	 * or by throwing) by flipping it to false afterwards.
	 */
	private function newParser( int $outputType, bool $interfaceMessage, bool &$locked ): Parser {
		$options = $this->createMock( ParserOptions::class );
		$options->method( 'getInterfaceMessage' )->willReturn( $interfaceMessage );

		$parser = $this->createMock( Parser::class );
		$parser->method( 'getOutputType' )->willReturn( $outputType );
		$parser->method( 'getOptions' )->willReturn( $options );
		$parser->method( 'isLocked' )->willReturnCallback(
			static function () use ( &$locked ) {
				return $locked;
			}
		);

		return $parser;
	}

	private function newContentParser( bool &$locked ): Parser {
		return $this->newParser( Parser::OT_HTML, false, $locked );
	}

	private function newInterfaceMessageParser( bool &$locked ): Parser {
		return $this->newParser( Parser::OT_HTML, true, $locked );
	}

	private function newPreprocessParser( bool &$locked ): Parser {
		return $this->newParser( Parser::OT_PREPROCESS, false, $locked );
	}

	/**
	 * #7009: modules registered by an outer parse must survive a nested parse
	 * (e.g. DynamicPageList) that also commits its own, discarded ParserOutput.
	 */
	public function testModulesSurviveNestedParse(): void {
		$outerLocked = true;
		$nestedLocked = true;
		$outer = $this->newContentParser( $outerLocked );
		$nested = $this->newContentParser( $nestedLocked );

		$outerPO = $this->newMockParserOutput();
		$nestedPO = $this->newMockParserOutput();

		// Outer page parse begins and an {{#ask}} registers a module.
		Outputs::onParseStart( $outer );
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		Outputs::requireStyle( 'smw.tableprinter.datatable.styles' );

		// DPL starts a nested parse that commits to its own ParserOutput.
		Outputs::onParseStart( $nested );
		Outputs::commitToParserOutput( $nestedPO );

		// Nested parse ends and its parser unlocks.
		Outputs::onParseEnd( $nested );
		$nestedLocked = false;

		// The outer parse commits: it must still receive the module.
		Outputs::commitToParserOutput( $outerPO );
		Outputs::onParseEnd( $outer );

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
	 * Modules from one page must not leak into the next page parsed in the
	 * same process (batch/job scenario).
	 */
	public function testModulesDoNotLeakAcrossPages(): void {
		$aLocked = true;
		$bLocked = true;
		$pageA = $this->newContentParser( $aLocked );
		$pageB = $this->newContentParser( $bLocked );

		$pageAPO = $this->newMockParserOutput();
		$pageBPO = $this->newMockParserOutput();

		Outputs::onParseStart( $pageA );
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		Outputs::commitToParserOutput( $pageAPO );
		Outputs::onParseEnd( $pageA );
		$aLocked = false;

		Outputs::onParseStart( $pageB );
		Outputs::commitToParserOutput( $pageBPO );
		Outputs::onParseEnd( $pageB );

		$this->assertContains( 'smw.tableprinter.datatable', $pageAPO->getModules() );
		$this->assertNotContains(
			'smw.tableprinter.datatable',
			$pageBPO->getModules(),
			'Page B must not inherit page A\'s datatable module'
		);
	}

	/**
	 * Regression for the interface-message clearing bug: a standalone
	 * `Message::parse()` (interface message) is a balanced top-level parse, but
	 * it must not clear the buffer of a surrounding special-page render that
	 * registered a module and flushes it later via commitToOutputPage(). The
	 * intro/outro parse of a `#ask` result on Special:Ask is exactly this case.
	 */
	public function testInterfaceMessageParseDoesNotClearBuffer(): void {
		$msgLocked = true;
		$message = $this->newInterfaceMessageParser( $msgLocked );
		$po = $this->newMockParserOutput();

		// Special page registers a module before rendering its output.
		Outputs::requireResource( 'smw.tableprinter.datatable' );

		// An intro/outro Message::parse() runs and finishes. It commits nothing
		// (InternalParseBeforeLinks skips interface messages) and must leave the
		// buffer untouched.
		Outputs::onParseStart( $message );
		Outputs::onParseEnd( $message );
		$msgLocked = false;

		// The special page now flushes: the module must still be there.
		Outputs::commitToParserOutput( $po );

		$this->assertContains(
			'smw.tableprinter.datatable',
			$po->getModules(),
			'A nested interface-message parse must not wipe a pending special-page buffer'
		);
	}

	/**
	 * Regression for the preprocess/getPreloadText imbalance: those calls fire
	 * ParserClearState but never ParserAfterTidy, so a depth counter would drift
	 * upward and permanently defeat cross-page isolation. They run with a
	 * non-HTML output type and so must not be tracked at all.
	 */
	public function testPreprocessCallsDoNotBreakIsolation(): void {
		// Simulate several preprocess()/getPreloadText() calls that only ever
		// "start" (a counter would ratchet up here and never come back down).
		$ppLocked = true;
		for ( $i = 0; $i < 3; $i++ ) {
			Outputs::onParseStart( $this->newPreprocessParser( $ppLocked ) );
		}

		$aLocked = true;
		$bLocked = true;
		$pageA = $this->newContentParser( $aLocked );
		$pageB = $this->newContentParser( $bLocked );
		$pageAPO = $this->newMockParserOutput();
		$pageBPO = $this->newMockParserOutput();

		Outputs::onParseStart( $pageA );
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		Outputs::commitToParserOutput( $pageAPO );
		Outputs::onParseEnd( $pageA );
		$aLocked = false;

		Outputs::onParseStart( $pageB );
		Outputs::commitToParserOutput( $pageBPO );
		Outputs::onParseEnd( $pageB );

		$this->assertContains( 'smw.tableprinter.datatable', $pageAPO->getModules() );
		$this->assertNotContains(
			'smw.tableprinter.datatable',
			$pageBPO->getModules(),
			'preprocess()/getPreloadText() must not affect cross-page isolation'
		);
	}

	/**
	 * If a parse aborts (throws) before its ParserAfterTidy fires, its entry is
	 * never drained, but the parser unlocks. The nesting check must ignore such
	 * stale, unlocked entries so later pages are not permanently contaminated.
	 */
	public function testNestingCheckSelfHealsAfterAbortedParse(): void {
		$aLocked = true;
		$pageA = $this->newContentParser( $aLocked );

		// Page A starts, registers a module, then throws before committing or
		// ending: no commitToParserOutput(), no onParseEnd(). Its parser unlocks.
		Outputs::onParseStart( $pageA );
		Outputs::requireResource( 'smw.tableprinter.datatable' );
		$aLocked = false;

		// Page B runs to completion; its commit is treated as outermost (A is
		// unlocked) and clears the buffer. Page B may still inherit A's
		// already-registered module once (A never committed to clear it), the
		// same bounded single-page bleed the original master had; what matters
		// is that it does not persist, which page C below verifies.
		$bLocked = true;
		$pageB = $this->newContentParser( $bLocked );
		$pageBPO = $this->newMockParserOutput();
		Outputs::onParseStart( $pageB );
		Outputs::commitToParserOutput( $pageBPO );
		Outputs::onParseEnd( $pageB );
		$bLocked = false;

		// Page C must be clean: the leak from the aborted parse A does not persist.
		$cLocked = true;
		$pageC = $this->newContentParser( $cLocked );
		$pageCPO = $this->newMockParserOutput();
		Outputs::onParseStart( $pageC );
		Outputs::commitToParserOutput( $pageCPO );
		Outputs::onParseEnd( $pageC );

		$this->assertNotContains(
			'smw.tableprinter.datatable',
			$pageCPO->getModules(),
			'An aborted parse must not permanently contaminate later pages'
		);
	}

	/**
	 * Head items and scripts must follow the same lifecycle as modules: survive
	 * a nested parse, reach the outer page, and not leak into the next page.
	 */
	public function testHeadItemsAndScriptsFollowModuleLifecycle(): void {
		$outerLocked = true;
		$nestedLocked = true;
		$nextLocked = true;
		$outer = $this->newContentParser( $outerLocked );
		$nested = $this->newContentParser( $nestedLocked );
		$next = $this->newContentParser( $nextLocked );

		$outerPO = $this->newMockParserOutput();
		$nestedPO = $this->newMockParserOutput();
		$nextPO = $this->newMockParserOutput();

		Outputs::onParseStart( $outer );
		Outputs::requireHeadItem( 'smw-head', '<link rel="stylesheet">' );
		Outputs::requireScript( 'smw-script', '<script>1</script>' );

		// Nested parse commits and must preserve the buffers.
		Outputs::onParseStart( $nested );
		Outputs::commitToParserOutput( $nestedPO );
		Outputs::onParseEnd( $nested );
		$nestedLocked = false;

		// Outer commit: the items reach the page and the buffers are cleared.
		Outputs::commitToParserOutput( $outerPO );
		Outputs::onParseEnd( $outer );
		$outerLocked = false;

		// A following page registers nothing.
		Outputs::onParseStart( $next );
		Outputs::commitToParserOutput( $nextPO );
		Outputs::onParseEnd( $next );

		$this->assertArrayHasKey( 'smw-head', $outerPO->getHeadItems() );
		$this->assertArrayHasKey( 'smw-script', $outerPO->getHeadItems() );
		$this->assertArrayNotHasKey(
			'smw-head',
			$nextPO->getHeadItems(),
			'Head item must not leak into the next page'
		);
		$this->assertArrayNotHasKey(
			'smw-script',
			$nextPO->getHeadItems(),
			'Script must not leak into the next page'
		);
	}

	/**
	 * commitToParserOutput outside of any parse (special pages calling
	 * commitToParser directly) must still clear the buffers.
	 */
	public function testCommitOutsideParseClears(): void {
		$po1 = $this->newMockParserOutput();
		$po2 = $this->newMockParserOutput();

		Outputs::requireResource( 'ext.smw.ask' );
		Outputs::commitToParserOutput( $po1 );

		// The second commit must see empty buffers.
		Outputs::commitToParserOutput( $po2 );

		$this->assertContains( 'ext.smw.ask', $po1->getModules() );
		$this->assertNotContains( 'ext.smw.ask', $po2->getModules() );
	}
}
