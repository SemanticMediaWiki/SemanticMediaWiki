<?php

namespace SMW\Tests;

use SMW\Application;

use Title;

/**
 * @covers \SMW\Application
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		Application::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Application',
			Application::getInstance()
		);
	}

	public function testCanConstructSerializerFactory() {

		$this->assertInstanceOf(
			'\SMW\SerializerFactory',
			Application::getInstance()->newSerializerFactory()
		);
	}

	public function testCanConstructJobFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\JobFactory',
			Application::getInstance()->newJobFactory()
		);
	}

	public function testGetStore() {

		$this->assertInstanceOf(
			'\SMW\Store',
			Application::getInstance()->getStore()
		);
	}

	public function testGetSettings() {

		$this->assertInstanceOf(
			'\SMW\Settings',
			Application::getInstance()->getSettings()
		);
	}

	public function testCanConstructTitleCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			Application::getInstance()->newTitleCreator()
		);
	}

	public function testCanConstructPageCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageCreator',
			Application::getInstance()->newPageCreator()
		);
	}

	public function testCanConstructPropertyAnnotatorFactory() {

		$this->assertInstanceOf(
			'\SMW\Annotator\PropertyAnnotatorFactory',
			Application::getInstance()->newPropertyAnnotatorFactory()
		);
	}

	public function testCanConstructFactboxBuilder() {

		$this->assertInstanceOf(
			'SMW\Factbox\FactboxBuilder',
			Application::getInstance()->newFactboxBuilder()
		);
	}

	public function testCanConstructInTextAnnotationParser() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\InTextAnnotationParser',
			Application::getInstance()->newInTextAnnotationParser( $parserData )
		);
	}

	public function testCanConstructContentParser() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ContentParser',
			Application::getInstance()->newContentParser( $title )
		);
	}

}
