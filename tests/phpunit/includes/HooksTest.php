<?php

namespace SMW\Test;

use SMWHooks;
use User;
use Title;
use WikiPage;
use ParserOutput;
use Parser;
use LinksUpdate;

/**
 * Tests for the SMW\Hooks class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * This class tests implemented hooks and verifies consistency among those
 * invoked methods and ensures a hook generally returns with true.
 *
 * @ingroup SMW
 * @ingroup Test
 */
class HooksTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getTextDataProvider() {
		return array(
			array(
				'Fooooobaaaa',
				'TestUser',
				"[[Lorem ipsum]] dolor sit amet, consetetur sadipscing elitr, sed diam " .
				" nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat."
			),
		);
	}

	/**
	 * Helper method to normalize a path
	 *
	 * @since 1.9
	 */
	private function normalizePath( $path ) {
		return str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
	}

	/**
	 * Helper method that returns a Title object
	 *
	 * @param $titleName
	 *
	 * @return Title
	 */
	private function getTitle( $titleName ){
		return Title::newFromText( $titleName );
	}

	/**
	 * Helper method to create Title/ParserOutput object
	 *
	 * @see LinksUpdateTest::makeTitleAndParserOutput
	 *
	 * @param $titleName
	 * @param $id
	 *
	 * @return array
	 */
	private function makeTitleAndParserOutput( $name, $id ) {
		$t = $this->getTitle( $name );
		$t->resetArticleID( $id );

		$po = new ParserOutput();
		$po->setTitleText( $t->getPrefixedText() );

		return array( $t, $po );
	}

	/**
	 * Helper method to create Parser object
	 *
	 * @param $titleName
	 *
	 * @return Parser
	 */
	private function getParser( $titleName ) {
		global $wgContLang, $wgParserConf;

		$title = $this->getTitle( $titleName );
		$wikiPage = new WikiPage(  $title );
		$user = User::newFromName( $titleName );
		$parserOptions = $wikiPage->makeParserOptions( $user );

		$parser = new Parser( $wgParserConf );
		$parser->setTitle( $title );
		$parser->setUser( $user );
		$parser->Options( $parserOptions );
		$parser->clearState();
		return $parser;
	}

	/**
	 * Test SMWHooks::onArticleFromTitle
	 *
	 * @since 1.9
	 */
	public function testOnArticleFromTitle() {
		$title = Title::newFromText( 'Property', SMW_NS_PROPERTY );
		$wikiPage = new WikiPage( $title );

		$result = SMWHooks::onArticleFromTitle( $title, $wikiPage );
		$this->assertTrue( $result );

		$title = Title::newFromText( 'Concepts', SMW_NS_CONCEPT );
		$wikiPage = new WikiPage( $title );

		$result = SMWHooks::onArticleFromTitle( $title, $wikiPage );
		$this->assertTrue( $result );
	}

	/**
	 * Test SMWHooks::onParserFirstCallInit
	 *
	 * @since 1.9
	 */
	public function testOnParserFirstCallInit() {
		$parser = $this->getParser( 'FooBaroo' );
		$result = SMWHooks::onParserFirstCallInit( $parser );

		$this->assertTrue( $result );
	}

	/**
	 * Test SMWHooks::onSpecialStatsAddExtra
	 *
	 * @since 1.9
	 */
	public function testOnSpecialStatsAddExtra() {
		$extraStats = array();
		$result = SMWHooks::onSpecialStatsAddExtra( $extraStats );

		$this->assertTrue( $result );
	}

	/**
	 * Test SMWHooks::onParserAfterTidy
	 *
	 * @since 1.9
	 *
	 * @dataProvider getTextDataProvider
	 * @param $text
	 */
	public function testOnParserAfterTidy( $text ) {
		$parser = $this->getParser( 'BarFoo' );
		$result = SMWHooks::onParserAfterTidy(
			$parser,
			$text
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test SMWHooks::onLinksUpdateConstructed
	 *
	 * @since 1.9
	 */
	public function testOnLinksUpdateConstructed() {
		list( $title, $parserOutput ) = $this->makeTitleAndParserOutput( "Testing", 111 );
		$update = new LinksUpdate( $title, $parserOutput );
		$result = SMWHooks::onLinksUpdateConstructed( $update );

		$this->assertTrue( $result );
	}

	/**
	 * Test SMWHooks::onArticleDelete
	 *
	 * @since 1.9
	 *
	 * @dataProvider getTextDataProvider
	 * @param $titleName
	 * @param $userName
	 */
	public function testOnArticleDelete( $titleName, $userName ) {
		if ( method_exists( 'WikiPage', 'doEditContent' ) ) {

			$title = $this->getTitle( $titleName );
			$wikiPage = new WikiPage(  $title );
			$revision = $wikiPage->getRevision();
			$user = User::newFromName( $userName );
			$reason = '';
			$error = '';

			$result = SMWHooks::onArticleDelete(
				$wikiPage,
				$user,
				$reason,
				$error
			);

			$this->assertTrue( $result );
		} else {
			$this->markTestSkipped(
				'Skipped test due to missing method (probably MW 1.19 or lower).'
			);
		}
	}

	/**
	 * Test SMWHooks::onNewRevisionFromEditComplete
	 *
	 * @since 1.9
	 *
	 * @dataProvider getTextDataProvider
	 * @param $titleName
	 * @param $userName
	 * @param $text
	 */
	public function testOnNewRevisionFromEditComplete( $titleName, $userName, $text ) {
		if ( method_exists( 'WikiPage', 'doEditContent' ) ) {

			$title = $this->getTitle( $titleName );
			$wikiPage = new WikiPage(  $title );

			$content = \ContentHandler::makeContent(
				$text,
				$title,
				CONTENT_MODEL_WIKITEXT
			);

			$wikiPage->doEditContent( $content, "testing", EDIT_NEW );
			$this->assertTrue( $wikiPage->getId() > 0, "WikiPage should have new page id" );
			$revision = $wikiPage->getRevision();
			$user = User::newFromName( $userName );

			$result = SMWHooks::onNewRevisionFromEditComplete (
				$wikiPage,
				$revision,
				$wikiPage->getId(),
				$user
			);

			$this->assertTrue( $result );
		} else {
			$this->markTestSkipped(
				'Skipped test due to missing method (probably MW 1.19 or lower).'
			);
		}
	}

	/**
	 * Test SMWHooks::registerUnitTests
	 *
	 * Files are normally registered manually in registerUnitTests(). This test
	 * will compare registered files with the files available in the
	 * test directory.
	 *
	 * @since 1.9
	 */
	public function testRegisterUnitTests() {
		$registeredFiles = array();
		$result = SMWHooks::registerUnitTests( $registeredFiles );

		$this->assertTrue( $result );
		$this->assertNotEmpty( $registeredFiles );

		// Get all the *.php files
		// @see http://php.net/manual/en/class.recursivedirectoryiterator.php
		$testFiles = new \RegexIterator(
			new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( __DIR__ ) ),
			'/^.+\.php$/i',
			\RecursiveRegexIterator::GET_MATCH
		);

		// Array contains files that are excluded from verification because
		// those files do not contain any executable tests and therefore are
		// not registered (such as abstract classes, mock classes etc.)
		$excludedFiles = array(
			'dataitems/DataItem',
			'printers/ResultPrinter'
		);

		// Normalize excluded files
		foreach ( $excludedFiles as &$registeredFile ) {
			$registeredFile = $this->normalizePath( __DIR__ . '/' . $registeredFile . 'Test.php' );
		}

		// Normalize registered files
		foreach ( $registeredFiles as &$registeredFile ) {
			$registeredFile = $this->normalizePath( $registeredFile );
		}

		// Compare directory files with registered files
		foreach ( $testFiles as $fileName => $object ){
			$fileName = $this->normalizePath( $fileName );

			if ( !in_array( $fileName, $excludedFiles ) ) {
				$this->assertContains(
					$fileName,
					$registeredFiles,
					'Missing registration for ' . $fileName
				);
			}
		}
	}
}
