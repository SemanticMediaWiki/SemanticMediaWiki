<?php

namespace SMW\Test;

use ContentHandler;
use Parser;
use Revision;
use SMW\ContentParser;
use TextContent;
use TextContentHandler;
use Title;

/**
 * @covers \SMW\ContentParser
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class ContentParserTest extends SemanticMediaWikiTestCase {

	public function getClass() {
		return '\SMW\ContentParser';
	}

	private function newInstance( Title $title = null, $parser = null ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		return new ContentParser( $title, $parser );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testCanParseOnInstance() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance()->parse() );
	}

	/**
	 * @depends testCanParseOnInstance
	 */
	public function testRunParseOnText() {

		$text     = 'Foo-1-' . __METHOD__;
		$expected = '<p>' . $text . "\n" . '</p>';

		$this->assertParserOutput( $expected, $this->newInstance()->parse( $text ) );
	}

	/**
	 * @dataProvider titleRevisionDataProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testRunParseOnTitle( $setup, $expected, $withContentHandler = false ) {

		$instance = $this->getMock( $this->getClass(),
			array( 'hasContentHandler' ),
			array(
				$setup['title'],
				new Parser()
			)
		);

		$instance->expects( $this->any() )
			->method( 'hasContentHandler' )
			->will( $this->returnValue( $withContentHandler ) );

		$instance->setRevision( $setup['revision'] );

		$this->assertInstanceAfterParse( $expected, $instance->parse() );

	}

	/**
	 * @dataProvider contentDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunParseOnTitleWithContentHandler( $setup, $expected ) {

		if ( !class_exists( 'ContentHandler') ) {
			$this->markTestSkipped(
				'Skipping test due to missing class (probably MW 1.21 or lower).'
			);
		}

		$this->testRunParseOnTitle( $setup, $expected, true );
	}

	protected function assertInstanceAfterParse( $expected, $instance ) {

		$this->assertInstanceOf( $this->getClass(), $instance );

		if ( $expected['error'] ) {
			return $this->assertError( $instance );
		}

		$this->assertParserOutput( $expected['text'], $instance );
	}

	protected function assertError( $instance ) {
		$this->assertInternalType( 'array', $instance->getErrors() );
		$this->assertNotEmpty( $instance->getErrors() );
	}

	protected function assertParserOutput( $text, $instance ) {

		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );

		if ( $text !== '' ) {

			return $this->assertContains(
				$text,
				$instance->getOutput()->getText(),
				'Asserts that getText() returns expected text component'
			);

		}

		$this->assertEmpty( $instance->getOutput()->getText() );
	}

	/**
	 * @return array
	 */
	public function titleRevisionDataProvider() {

		$provider = array();

		$text     = 'Foo-2-' . __METHOD__;
		$expected ='<p>' . $text . "\n" . '</p>';

		// #0 Title does not exists
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => null,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		// #1 Valid revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'   => 9001,
			'getUser' => 'Lala',
			'getText' => $text,
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $expected
			)
		);

		// #2 Null revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = null;

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function contentDataProvider() {

		$provider = array();

		if ( !class_exists( 'ContentHandler') ) {
			$provider[] = array( array(), array() );
			return $provider;
		}

		$text     = 'Foo-3-' . __METHOD__;

		// #0 Title does not exists
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => null,
			),
			array(
				'error'    => true,
				'text'     => ''
			)
		);

		// #1 Valid revision
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'      => 9001,
			'getUser'    => 'Lala',
			'getContent' => new TextContent( $text )
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $text
			)
		);

		// #1 Empty content
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage(),
			'getContentModel' => CONTENT_MODEL_WIKITEXT
		) );

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'             => 9001,
			'getUser'           => 'Lala',
			'getContent'        => false,
			'getContentHandler' => new TextContentHandler()
		) );

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => ''
			)
		);

		// #2 "real" revision and content
		$title    = $this->newTitle();
		$content  = ContentHandler::makeContent( $text, $title, CONTENT_MODEL_WIKITEXT, null );

		$revision = new Revision(
			array(
				'id'         => 42,
				'page'       => 23,
				'title'      => $title,

				'content'    => $content,
				'length'     => $content->getSize(),
				'comment'    => "testing",
				'minor_edit' => false,

				'content_format' => null,
			)
		);

		$provider[] = array(
			array(
				'title'    => $title,
				'revision' => $revision,
			),
			array(
				'error'    => false,
				'text'     => $text
			)
		);

		return $provider;
	}

}
