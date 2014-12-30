<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\Query\Language\SomeProperty;
use SMW\DataValueFactory;

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
class PageTypeRegexQueryTest extends MwDBaseUnitTestCase {

	private $queryResultValidator;
	private $fixturesProvider;

	private $semanticDataFactory;
	private $dataValueFactory;

	private $queryParser;
	private $subjects = array();

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = $utilityFactory->newSemanticDataFactory();

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
	 * #649
	 */
	public function testPageLikeNotLikeWildcardSearch() {

		$property = $this->fixturesProvider->getProperty( 'title' );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-0' );

		$this->subjects['sample-0'] = $semanticData->getSubject();

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			"Title never to be selected"
		);

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-1' );

		$this->subjects['sample-1'] = $semanticData->getSubject();

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			"Sample text with spaces"
		);

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ .'-2'  );

		$this->subjects['sample-2'] = $semanticData->getSubject();

		$dataValue = $this->dataValueFactory->newPropertyObjectValue(
			$property,
			"Sample test with spaces"
		);

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		/**
		 * @query "[[Title::~Sample te*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Title::~Sample te*]]" ,
			array(
				$this->subjects['sample-1'],
				$this->subjects['sample-2']
			)
		);

		/**
		 * @query "[[Title::~Sample tes*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Title::~Sample tes*]]",
			array(
				$this->subjects['sample-2']
			)
		);

		/**
		 * @query "[[Title::~Sample te?t with spaces]]"
		 */
		$this->assertThatQueryReturns(
			"[[Title::~Sample te?t with spaces]]",
			array(
				$this->subjects['sample-1'],
				$this->subjects['sample-2']
			)
		);

		/**
		 * @query "[[Title::~Sample*]] [[Title::!~Sample tes*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Title::~Sample*]] [[Title::!~Sample tes*]]",
			array(
				$this->subjects['sample-1']
			)
		);

		/**
		 * @query "[[Title::~Sample tes*]] OR [[Title::~Sample tex*]]"
		 */
		$this->assertThatQueryReturns(
			"[[Title::~Sample tes*]] OR [[Title::~Sample tex*]]",
			array(
				$this->subjects['sample-1'],
				$this->subjects['sample-2']
			)
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

		$query->sort = true;
		$query->sortkeys = array(
			'Title' => 'desc'
		);

		$query->setUnboundLimit( 1000 );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expected,
			$this->getStore()->getQueryResult( $query )
		);
	}

}
