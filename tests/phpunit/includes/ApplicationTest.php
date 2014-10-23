<?php

namespace SMW\Tests;

use SMW\Application;

/**
 * @covers \SMW\Application
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

	private $application;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();
	}

	protected function tearDown() {
		$this->application->clear();

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
			$this->application->newSerializerFactory()
		);
	}

	public function testCanConstructJobFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\JobFactory',
			$this->application->newJobFactory()
		);
	}

	public function testCanConstructParserFunctionFactory() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctionFactory',
			$this->application->newParserFunctionFactory( $parser )
		);
	}

	public function testCanConstructQueryProfilerFactory() {

		$this->assertInstanceOf(
			'\SMW\Query\Profiler\QueryProfilerFactory',
			$this->application->newQueryProfilerFactory()
		);
	}

	public function testGetStore() {

		$this->assertInstanceOf(
			'\SMW\Store',
			$this->application->getStore()
		);
	}

	public function testGetSettings() {

		$this->assertInstanceOf(
			'\SMW\Settings',
			$this->application->getSettings()
		);
	}

	public function testCanConstructTitleCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			$this->application->newTitleCreator()
		);
	}

	public function testCanConstructPageCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageCreator',
			$this->application->newPageCreator()
		);
	}

	public function testCanConstructPropertyAnnotatorFactory() {

		$this->assertInstanceOf(
			'\SMW\Annotator\PropertyAnnotatorFactory',
			$this->application->newPropertyAnnotatorFactory()
		);
	}

	public function testCanConstructFactboxBuilder() {

		$this->assertInstanceOf(
			'SMW\Factbox\FactboxBuilder',
			$this->application->newFactboxBuilder()
		);
	}

	public function testCanConstructInTextAnnotationParser() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\InTextAnnotationParser',
			$this->application->newInTextAnnotationParser( $parserData )
		);
	}

	public function testCanConstructContentParser() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ContentParser',
			$this->application->newContentParser( $title )
		);
	}

	public function testCanConstructMessageBuilder() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$this->application->newMessageBuilder()
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MessageBuilder',
			$this->application->newMessageBuilder( $language )
		);
	}

	public function testCanConstructHtmlFormBuilder() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlFormBuilder',
			$this->application->newHtmlFormBuilder( $title )
		);

		$language = $this->getMockBuilder( '\Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\HtmlFormBuilder',
			$this->application->newHtmlFormBuilder( $title, $language )
		);
	}

	public function testCanConstructNamespaceExaminer() {

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			$this->application->getNamespaceExaminer()
		);
	}

}
