<?php

namespace SMW\Tests;

use SMW\ApplicationFactory;

/**
 * @covers \SMW\ApplicationFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ApplicationFactoryTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ApplicationFactory',
			ApplicationFactory::getInstance()
		);
	}

	public function testCanConstructSerializerFactory() {

		$this->assertInstanceOf(
			'\SMW\SerializerFactory',
			$this->applicationFactory->newSerializerFactory()
		);
	}

	public function testCanConstructJobFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Jobs\JobFactory',
			$this->applicationFactory->newJobFactory()
		);
	}

	public function testCanConstructParserFunctionFactory() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctionFactory',
			$this->applicationFactory->newParserFunctionFactory( $parser )
		);
	}

	public function testCanConstructQueryProfilerFactory() {

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory',
			$this->applicationFactory->newQueryProfileAnnotatorFactory()
		);
	}

	public function testGetStore() {

		$this->assertInstanceOf(
			'\SMW\Store',
			$this->applicationFactory->getStore()
		);
	}

	public function testGetSettings() {

		$this->assertInstanceOf(
			'\SMW\Settings',
			$this->applicationFactory->getSettings()
		);
	}

	public function testCanConstructTitleCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleCreator',
			$this->applicationFactory->newTitleCreator()
		);
	}

	public function testCanConstructPageCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageCreator',
			$this->applicationFactory->newPageCreator()
		);
	}

	public function testCanConstructPropertyAnnotatorFactory() {

		$this->assertInstanceOf(
			'\SMW\PropertyAnnotatorFactory',
			$this->applicationFactory->newPropertyAnnotatorFactory()
		);
	}

	public function testCanConstructFactboxFactory() {

		$this->assertInstanceOf(
			'SMW\Factbox\FactboxFactory',
			$this->applicationFactory->newFactboxFactory()
		);
	}

	public function testCanConstructInTextAnnotationParser() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\InTextAnnotationParser',
			$this->applicationFactory->newInTextAnnotationParser( $parserData )
		);
	}

	public function testCanConstructContentParser() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ContentParser',
			$this->applicationFactory->newContentParser( $title )
		);
	}

	public function testCanConstructMwCollaboratorFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MwCollaboratorFactory',
			$this->applicationFactory->newMwCollaboratorFactory()
		);
	}

	public function testCanConstructNamespaceExaminer() {

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			$this->applicationFactory->getNamespaceExaminer()
		);
	}

	public function testCanConstructStoreUpdater() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\StoreUpdater',
			$this->applicationFactory->newStoreUpdater( $semanticData )
		);
	}

	public function testCanConstructQueryParser() {

		$this->assertInstanceOf(
			'\SMWQueryParser',
			$this->applicationFactory->newQueryParser()
		);
	}

	public function testCanConstructMaintenanceFactory() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceFactory',
			$this->applicationFactory->newMaintenanceFactory()
		);
	}

	public function testCanConstructCacheFactory() {

		$this->assertInstanceOf(
			'\SMW\CacheFactory',
			$this->applicationFactory->newCacheFactory()
		);
	}

}
