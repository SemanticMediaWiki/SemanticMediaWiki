<?php

namespace SMW\Test;

use SMW\ContentParser;

use Title;

/**
 * Tests for the ContentParser class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\ContentParser
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ContentParserTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ContentParser';
	}

	/**
	 * Helper method that returns a ContentParser object
	 *
	 * @since 1.9
	 *
	 * @return ContentParser
	 */
	private function getInstance( Title $title = null ) {
		return new ContentParser( $title === null ? $this->newTitle() : $title );
	}

	/**
	 * @test ContentParser::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ContentParser::parse
	 *
	 * @since 1.9
	 */
	public function testParseFromText() {

		$text     = $this->newRandomString( 20, __METHOD__ );
		$expected ='<p>' . $text . "\n" . '</p>';

		$instance = $this->getInstance();
		$instance->setText( $text )->parse();

		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		$this->assertEquals( $expected, $instance->getOutput()->getText() );

	}

	/**
	 * @test ContentParser::parse
	 * @dataProvider titleRevisionDataProvider
	 *
	 * @since 1.9
	 */
	public function testParseFromRevision( $setup, $expected ) {

		$reflector = $this->newReflector();
		$instance  = $this->getInstance( $setup['title'] );

		$revision  = $reflector->getProperty( 'revision' );
		$revision->setAccessible( true );
		$revision->setValue( $instance, $setup['revision'] );

		$instance->parse();

		if ( $expected['error'] ) {
			$this->assertInternalType( 'array', $instance->getErrors() );
		} else {
			$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
			$this->assertEquals( $expected['text'], $instance->getOutput()->getText() );
		}

	}

	/**
	 * Provides title and wikiPage samples
	 *
	 * @return array
	 */
	public function titleRevisionDataProvider() {

		$provider = array();

		$text     = $this->newRandomString( 20, __METHOD__ );
		$expected ='<p>' . $text . "\n" . '</p>';

		// #0 Title does not exists
		$title = $this->newMockObject( array(
			'getDBkey'        => 'Lila',
			'exists'          => false,
			'getText'         => null,
			'getPageLanguage' => $this->getLanguage()
		) )->getMockTitle();

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
		$title = $this->newMockObject( array(
			'getDBkey'        => 'Lula',
			'exists'          => true,
			'getPageLanguage' => $this->getLanguage()
		) )->getMockTitle();

		$revision = $this->newMockObject( array(
			'getId'   => 9001,
			'getUser' => 'Lala',
			'getText' => $text,
		) )->getMockRevision();

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

		return $provider;
	}

}
