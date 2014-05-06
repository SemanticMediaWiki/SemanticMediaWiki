<?php

namespace SMW\Tests\DIC;

use SMW\DIC\ObjectFactory;

use ParserOutput;
use Title;

/**
 * @covers \SMW\DIC\ObjectFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9.3
 *
 * @author mwjames
 */
class ObjectFactoryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		ObjectFactory::clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DIC\ObjectFactory',
			ObjectFactory::getInstance()
		);
	}

	public function testGetSettings() {

		$this->assertInstanceOf(
			'\SMW\Settings',
			ObjectFactory::getInstance()->getSettings()
		);
	}

	public function testNewByParserData() {

		$title = Title::newFromText( __METHOD__ );

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserData',
			ObjectFactory::getInstance()->newByParserData( $title, $parserOutput )
		);
	}

	public function testNewInTextAnnotationParser() {

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$this->assertInstanceOf(
			'\SMW\InTextAnnotationParser',
			ObjectFactory::getInstance()->newInTextAnnotationParser( $parserData )
		);
	}

	public function testNewMagicWordFinder() {

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MagicWordFinder',
			ObjectFactory::getInstance()->newMagicWordFinder( $parserOutput )
		);
	}

	public function testNewRedirectPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTarget = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\RedirectPropertyAnnotator',
			ObjectFactory::getInstance()->newRedirectPropertyAnnotator(
				$semanticData,
				$redirectTarget
			)
		);
	}

}
