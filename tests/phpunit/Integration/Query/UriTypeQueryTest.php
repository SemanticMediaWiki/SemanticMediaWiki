<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\SomeProperty;
use SMW\DataValueFactory;
use SMW\DIWikiPage;

use SMWQueryParser as QueryParser;
use SMWQuery as Query;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWExporter as Exporter;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group semantic-mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UriTypeQueryTest extends MwDBaseUnitTestCase {

	private $queryResultValidator;
	private $fixturesProvider;

	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryParser;
	private $subjects = array();

	private $pageCreator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();

		$this->queryResultValidator = $utilityFactory->newValidatorFactory()->newQueryResultValidator();

		$this->fixturesProvider = $utilityFactory->newFixturesFactory()->newFixturesProvider();
		$this->fixturesProvider->setupDependencies( $this->getStore() );

		$this->queryParser = new QueryParser();
	}

	protected function tearDown() {

		$fixturesCleaner = UtilityFactory::getInstance()->newFixturesFactory()->newFixturesCleaner();

		$fixturesCleaner
			->purgeSubjects( $this->subjects )
			->purgeAllKnownFacts();

		parent::tearDown();
	}

	/**
	 * T45264
	 */
	public function testQuerySearchPatternForUriType() {

		$property = $this->fixturesProvider->getProperty( 'url' );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-0' );

		$this->subjects['sample-0'] = $semanticData->getSubject();

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			"http://example.org/aaa/bbb#ccc"
		);

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-1' );

		$this->subjects['sample-1'] = $semanticData->getSubject();

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			"http://example.org/api?query=!_:;@* #Foo&=%20-3DBar"
		);

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		/**
		 * @query "[[Url::http://example.org/aaa/bbb#ccc]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::http://example.org/aaa/bbb#ccc]]" ,
			array(
				$this->subjects['sample-0']
			)
		);

		/**
		 * @query "[[Url::http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]]" ,
			array(
				$this->subjects['sample-1']
			)
		);

		/**
		 * @query "[[Url::~*http://example.org/*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::~*http://example.org/*]]" ,
			array(
				$this->subjects['sample-0'],
				$this->subjects['sample-1']
			)
		);

		/**
		 * @query "[[Url::~*ccc*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::~*ccc*]]" ,
			array(
				$this->subjects['sample-0']
			)
		);

		/**
		 * @query "[[Url::~http://*query=*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::~http://*query=*]]" ,
			array(
				$this->subjects['sample-1']
			)
		);

		/**
		 * @query "[[Url::~http://*query=*]] OR [[Url::~*ccc*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Url::~http://*query=*]] OR [[Url::~*ccc*]]" ,
			array(
				$this->subjects['sample-0'],
				$this->subjects['sample-1']
			)
		);
	}

	public function testUsingInPageAnnotation() {

		$property = $this->fixturesProvider->getProperty( 'url' );

		$this->subjects['sample-0'] = DIWikiPage::newFromTitle( \Title::newFromText( __METHOD__ . '-0' ) );
		$this->subjects['sample-1'] = DIWikiPage::newFromTitle( \Title::newFromText( __METHOD__ . '-1' ) );

		/**
		 * @query "[[Url::http://example.org/aaa/bbb#ccc]]"
		 */
		$this->pageCreator
			->createPage( $this->subjects['sample-0']->getTitle() )
			->doEdit( '[[Url::http://example.org/aaa/bbb#ccc]]' );

		$this->assertThatQueryReturns(
			"[[Url::http://example.org/aaa/bbb#ccc]]" ,
			array(
				$this->subjects['sample-0']
			)
		);

		$this->assertThatSemanticDataContainsUri(
			'http://example.org/aaa/bbb#ccc',
			$this->subjects['sample-0'],
			$property
		);

		/**
		 * @query "[[Url::http://example.org/aaa/bbb#-2ccc]]"
		 */
		$this->pageCreator
			->createPage( $this->subjects['sample-1']->getTitle() )
			->doEdit( '[[Url::http://example.org/aaa/bbb#-2ccc]]' );

		$this->assertThatQueryReturns(
			"[[Url::http://example.org/aaa/bbb#-2ccc]]" ,
			array(
				$this->subjects['sample-1']
			)
		);

		$this->assertThatSemanticDataContainsUri(
			'http://example.org/aaa/bbb#-2ccc',
			$this->subjects['sample-1'],
			$property
		);

		/**
		 * @query "[[Url::http://example.org/mw-123/index.php?&value=http%3A%2F%2Fexample.org]]"
		 */
		$this->pageCreator
			->createPage( $this->subjects['sample-1']->getTitle() )
			->doEdit( '[[Url::http://example.org/mw-123/index.php?&value=http%3A%2F%2Fexample.org]]' );

		$this->assertThatQueryReturns(
			"[[Url::http://example.org/mw-123/index.php?&value=http%3A%2F%2Fexample.org]]" ,
			array(
				$this->subjects['sample-1']
			)
		);

		$this->assertThatSemanticDataContainsUri(
			'http://example.org/mw-123/index.php?&value=http%3A%2F%2Fexample.org',
			$this->subjects['sample-1'],
			$property
		);

		/**
		 * @query "[[Url::http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]]"
		 */
		$this->pageCreator
			->createPage( $this->subjects['sample-0']->getTitle() )
			->doEdit( '[[Url::http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]]' );

		$this->assertThatQueryReturns(
			"[[Url::http://example.org/api?query=!_:;@* #Foo&=%20-3DBar]]" ,
			array(
				$this->subjects['sample-0']
			)
		);

		$this->assertThatSemanticDataContainsUri(
			'http://example.org/api?query=%21_:%3B@%2A%20#Foo&=%20-3DBar',
			$this->subjects['sample-0'],
			$property
		);

		/**
		 * @query "[[Url::http://example.org/ようこそ#-{}]]"
		 */
		$this->pageCreator
			->createPage( $this->subjects['sample-0']->getTitle() )
			->doEdit( '[[Url::http://example.org/ようこそ#-{}]]' );

		$this->assertThatQueryReturns(
			"[[Url::http://example.org/ようこそ#-{}]]" ,
			array(
				$this->subjects['sample-0']
			)
		);

		$this->assertThatSemanticDataContainsUri(
			'http://example.org/%E3%82%88%E3%81%86%E3%81%93%E3%81%9D#-%7B%7D',
			$this->subjects['sample-0'],
			$property
		);
	}

	private function assertThatSemanticDataContainsUri( $uri, $subject, $property ) {

		$expected = array(
			'propertyValues' => array( $uri )
		);

		$this->semanticDataValidator->assertThatPropertyValuesAreSet(
			$expected,
			$property,
			$this->getStore()->getSemanticData( $subject )->getPropertyValues( $property )
		);
	}

	private function assertThatQueryReturns( $queryString, $expected ) {

		$description = $this->queryParser
			->getQueryDescription( $queryString );

		$query = new Query(
			$description,
			false,
			true
		);

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expected,
			$this->getStore()->getQueryResult( $query )
		);
	}

}
