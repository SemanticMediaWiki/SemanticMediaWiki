<?php

namespace SMW\Tests\MediaWiki;

use ParserOutput;
use SMW\MediaWiki\MagicWordsFinder;

/**
 * @covers \SMW\MediaWiki\MagicWordsFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class MagicWordsFinderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordsFinder',
			new MagicWordsFinder()
		);

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordsFinder',
			new MagicWordsFinder( $parserOutput )
		);
	}

	/**
	 * @dataProvider magicWordsProvider
	 */
	public function testFindMagicWordInText( $magicWord, $text, $expectedText, $expectedWord ) {

		$instance = new MagicWordsFinder();
		$word = $instance->findMagicWordInText( $magicWord, $text );

		$this->assertInternalType(
			'string',
			$word
		);

		$this->assertEquals(
			$expectedWord,
			$word
		);

		$this->assertEquals(
			$expectedText,
			$text
		);
	}

	public function testSetGetMagicWords() {

		$instance = new MagicWordsFinder(
			new ParserOutput()
		);

		$this->assertMagicWordFromParserOutput(
			$instance,
			array( 'Foo', '', 'Bar' ),
			array( 'Foo', 'Bar' )
		);
	}

	public function testSetGetMagicWordsOnLegacyStorage() {

		$instance = $this->getMockBuilder( '\SMW\MediaWiki\MagicWordsFinder' )
			->disableOriginalConstructor()
			->setMethods( array( 'hasExtensionData' ) )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasExtensionData' )
			->will( $this->returnValue( false ) );

		$instance->setOutput( new ParserOutput() );

		$this->assertMagicWordFromParserOutput(
			$instance,
			array( 'Foo', '', 'Bar' ),
			array( 'Foo', 'Bar' )
		);
	}

	protected function assertMagicWordFromParserOutput( $instance, $magicWord, $expectedMagicWords ) {

		$this->assertEmpty(
			$instance->getMagicWords()
		);

		$instance->pushMagicWordsToParserOutput( $magicWord );

		$this->assertEquals(
			$expectedMagicWords,
			$instance->getMagicWords()
		);
	}

	/**
	 * @return array
	 */
	public function magicWordsProvider() {

		$provider = array();

		$provider[] = array(
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit amet consectetuer auctor at quis',
			''
		);

		$provider[] = array(
			'SMW_NOFACTBOX',
			'Lorem ipsum dolor sit __NOFACTBOX__ amet consectetuer auctor at quis',
			'Lorem ipsum dolor sit  amet consectetuer auctor at quis',
			'SMW_NOFACTBOX'
		);

		$provider[] = array(
			'SMW_SHOWFACTBOX',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis __SHOWFACTBOX__',
			'Lorem ipsum dolor __NOFACTBOX__ sit amet consectetuer auctor at quis ',
			'SMW_SHOWFACTBOX'
		);

		return $provider;
	}

}
