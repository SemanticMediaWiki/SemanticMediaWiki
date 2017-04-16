<?php

namespace SMW\Tests\Integration\Parser;

use Title;
use WikiPage;
use Revision;
use User;
use ParserOptions;
use ContentHandler;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MwBug62856_ContentHandlerInternalParseBeforeLinksHookTest extends \MediaWikiTestCase {

	private $parser = null;
	private $wgHooks = null;

	protected $internalParseBeforeLinksHookRetrievedText = null;

	protected function setUp() {
		parent::setUp();

		$this->parser = $GLOBALS['wgParser'];

		// Eliminate hooks from testing
		$this->wgHooks = $GLOBALS['wgHooks'];
		$GLOBALS['wgHooks'] = array();

		// Property is used as bridge to catch the text component during
		// invocation of the hook
		$this->internalParseBeforeLinksHookRetrievedText =& $internalParseBeforeLinksHookRetrievedText;

		// Only register a single hook that is suppose to be executed during testing
		$GLOBALS['wgHooks']['InternalParseBeforeLinks'][] = function ( &$parser, &$text ) use( &$internalParseBeforeLinksHookRetrievedText ) {

			// Use pass-by-reference to transport the available content to
			// the test component
			$internalParseBeforeLinksHookRetrievedText = $text;
			return true;
		};
	}

	protected function tearDown() {
		$GLOBALS['wgHooks'] = $this->wgHooks;

		parent::tearDown();
	}

	/**
	 * @dataProvider textProvider
	 *
	 * Generally, the test could be written in a more compact outset but it is possible
	 * that some users are unfamiliar with unit testing and are confused and might blame
	 * a lack of clarity in the test procedure therefore we use a more expressive means
	 * of describing actions taken to force Bug 62856
	 */
	public function testCompareTextInvokedOnInternalParseBeforeLinksHook( $titleName, $expectedText ) {

		if ( !class_exists( 'ContentHandler' ) ) {
			$this->markTestSkipped(
				'Skipping test due to ContentHandler being not available for the test'
			);
		}

		$pageWithTextContent = $this->createPage( Title::newFromText( $titleName ) );

		$this->doPageEdit(
			$pageWithTextContent,
			$expectedText
		);

		$this->useParserDirectlyToFetchParserOutput( $pageWithTextContent );
		$textInvokedOnParserUse = $this->internalParseBeforeLinksHookRetrievedText;

		$this->useContentHandlerToFetchParserOutput( $pageWithTextContent );
		$textInvokedOnContentHandlerUse = $this->internalParseBeforeLinksHookRetrievedText;

		$this->assertSame( $expectedText, $textInvokedOnParserUse );
		$this->assertSame( $expectedText, $textInvokedOnContentHandlerUse );

		$this->assertEquals(
			$textInvokedOnParserUse,
			$textInvokedOnContentHandlerUse
		);
	}

	public function textProvider() {

		$provider = array();

		$provider[] = array(
			'MwBug62856_PageWithTextContent',
			'[[Lorem ipsum]] dolor sit amet ...'
		);

		$provider[] = array(
			'MwBug62856_PageWithRedirectContent',
			'#REDIRECT [[PageWithTextContent]]' // Bug 62856
		);

		return $provider;
	}

	protected function useParserDirectlyToFetchParserOutput( WikiPage $page ) {

		$revision = $this->getRevision( $page );

		return $this->parser->parse(
			$revision->getText(),
			$page->getTitle(),
			$this->makeParserOptions( $revision ),
			true,
			true,
			$revision->getID()
		);
	}

	protected function useContentHandlerToFetchParserOutput( WikiPage $page ) {

		$revision = $this->getRevision( $page );

		$content = $revision->getContent( Revision::RAW );

		if ( !$content ) {
			$content = $revision->getContentHandler()->makeEmptyContent();
		}

		return $content->getParserOutput(
			$page->getTitle(),
			$revision->getID(),
			null,
			true
		);
	}

	protected function getRevision( WikiPage $page ) {

		// Revision::READ_NORMAL is not specified in MW 1.19
		if ( defined( 'Revision::READ_NORMAL' ) ) {
			return Revision::newFromTitle( $page->getTitle(), false, Revision::READ_NORMAL );
		}

		return Revision::newFromTitle( $page->getTitle() );
	}

	protected function makeParserOptions( $revision ) {

		$user = null;

		if ( $revision !== null ) {
			$user = User::newFromId( $revision->getUser() );
		}

		return new ParserOptions( $user );
	}

	protected function createPage( Title $title ) {
		return new WikiPage( $title );
		return $this->doPageEdit( $page, __METHOD__, 'SMW integration test: create page' );
	}

	protected function doPageEdit( $page, $pageContent = '', $editMessage = '' ) {

		if ( class_exists( 'WikitextContent' ) ) {
			$content = new \WikitextContent( $pageContent );

			$page->doEditContent(
				$content,
				$editMessage
			);

		} else {
			$page->doEdit( $pageContent, $editMessage );
		}

		return $page;
	}

}
