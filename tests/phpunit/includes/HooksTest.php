<?php

namespace SMW\Test;

use SMW\Tests\MwDBaseUnitTestCase;

use SMWHooks;
use User;
use Title;
use WikiPage;
use ParserOutput;
use Parser;
use LinksUpdate;

/**
 * @covers \SMWHooks
 *
 * This class is testing implemented hooks and verifies consistency with its
 * invoked methods to ensure a hook generally returns true.
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 *
 * The database group creates temporary tables which allows for testing without
 * accessing the production database.
 * @group Database
 */
class HooksTest extends MwDBaseUnitTestCase {

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 *
	 * @return string
	 */
	private function newRandomString( $length = 10 ) {
		return substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Helper method that returns a Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	private function getTitle( $text = '', $namespace = NS_MAIN ) {
		return Title::newFromText( $text !== '' ? $text : $this->newRandomString(), $namespace);
	}

	/**
	 * Helper method that creates a new wikipage
	 *
	 * @since 1.9
	 *
	 * @return WikiPage
	 */
	protected function newPage( Title $title = null ) {
		$wikiPage = new WikiPage( $title === null ? $this->getTitle() : $title );
		return $wikiPage;
	}

	/**
	 * Helper method that returns an User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	private function getUser() {
		return User::newFromName( $this->newRandomString() );
	}

	/**
	 * Helper method that creates a Title/ParserOutput object
	 * @see LinksUpdateTest::makeTitleAndParserOutput
	 *
	 * @since 1.9
	 *
	 * @param $titleName
	 * @param $id
	 *
	 * @return array
	 */
	private function makeTitleAndParserOutput() {
		$t = $this->getTitle();
		$t->resetArticleID( rand( 1, 1000 ) );

		$po = new ParserOutput();
		$po->setTitleText( $t->getPrefixedText() );

		return array( $t, $po );
	}

	/**
	 * Helper method that returns a Article mock object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 *
	 * @return Article
	 */
	private function getArticleMock( Title $title ) {

		$article = $this->getMockBuilder( 'Article' )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		return $article;
	}

	/**
	 * Helper method that returns a Parser object
	 *
	 * @since 1.9
	 *
	 * @param $titleName
	 *
	 * @return Parser
	 */
	private function getParser() {
		global $wgContLang, $wgParserConf;

		$wikiPage = $this->newPage();
		$user = $this->getUser();
		$parserOptions = $wikiPage->makeParserOptions( $user );

		$parser = new Parser( $wgParserConf );
		$parser->setTitle( $wikiPage->getTitle() );
		$parser->setUser( $user );
		$parser->Options( $parserOptions );
		$parser->clearState();
		return $parser;
	}

	/**
	 * @test SMWHooks::onArticleFromTitle
	 *
	 * @since 1.9
	 */
	public function testOnArticleFromTitle() {
		$title = Title::newFromText( 'Property', SMW_NS_PROPERTY );
		$wikiPage = $this->newPage( $title );

		$result = SMWHooks::onArticleFromTitle( $title, $wikiPage );
		$this->assertTrue( $result );

		$title = Title::newFromText( 'Concepts', SMW_NS_CONCEPT );
		$wikiPage = $this->newPage( $title );

		$result = SMWHooks::onArticleFromTitle( $title, $wikiPage );
		$this->assertTrue( $result );
	}

}
