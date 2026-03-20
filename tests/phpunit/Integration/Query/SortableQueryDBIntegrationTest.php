<?php

namespace SMW\Tests\Integration\Query;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValues\PropertyValue;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\PrintRequest;
use SMW\Query\Query;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 *
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class SortableQueryDBIntegrationTest extends SMWIntegrationTestCase {

	private $subjectsToBeCleared = [];
	private $semanticDataFactory;
	private $queryResultValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->queryResultValidator = UtilityFactory::getInstance()->newValidatorFactory()->newQueryResultValidator();
		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
	}

	protected function tearDown(): void {
		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testDefaultSortedQueryResult() {
		$expectedSubjects = [
			new WikiPage( 'AA', NS_MAIN ),
			new WikiPage( 'AB', NS_MAIN ),
			new WikiPage( 'AC', NS_MAIN )
		];

		$property = new Property( 'SomePageProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$query = $this->createQueryForSamplePagesThatContain( $property, $expectedSubjects );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$expectedSubjects,
			$this->getStore()->getQueryResult( $query )
		);
	}

	/**
	 * Set limit to avoid:
	 *
	 * Virtuoso 22023 Error SR353: Sorted TOP clause specifies more then 10001
	 * rows to sort. Only 10000 are allowed.
	 *
	 * @see Virtuoso MaxSortedTopRows setting
	 */
	public function testAscendingOrderedQueryResult() {
		$expectedSubjects = [
			new WikiPage( 'AA', NS_MAIN ),
			new WikiPage( 'AB', NS_MAIN ),
			new WikiPage( 'AC', NS_MAIN )
		];

		$property = new Property( 'SomeAscendingPageProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$query = $this->createQueryForSamplePagesThatContain( $property, $expectedSubjects );

		$query->sort = true;
		$query->sortkeys = [ $property->getKey() => 'ASC' ];
		$query->setUnboundLimit( 50 );

		$this->assertResultOrder(
			$expectedSubjects,
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

	public function testDescendingOrderedQueryResult() {
		$expectedSubjects = [
			new WikiPage( 'AA', NS_MAIN ),
			new WikiPage( 'AB', NS_MAIN ),
			new WikiPage( 'AC', NS_MAIN )
		];

		$property = new Property( 'SomeDescendingPageProperty' );
		$property->setPropertyTypeId( '_wpg' );

		$query = $this->createQueryForSamplePagesThatContain( $property, $expectedSubjects );

		$query->sort = true;
		$query->sortkeys = [ $property->getKey() => 'DESC' ];
		$query->setUnboundLimit( 50 );

		$this->assertResultOrder(
			array_reverse( $expectedSubjects ),
			$this->getStore()->getQueryResult( $query )->getResults()
		);
	}

	public function createQueryForSamplePagesThatContain( $property, array &$expectedSubjects ) {
		foreach ( $expectedSubjects as $key => $expectedSubject ) {

			$subjectTitle = $expectedSubject->getTitle()->getText() . '-' . __METHOD__;
			$semanticData = $this->semanticDataFactory->newEmptySemanticData( $subjectTitle );

			$semanticData->addPropertyObjectValue( $property, $expectedSubject );
			$this->subjectsToBeCleared[] = $semanticData->getSubject();
			$expectedSubjects[$key] = $semanticData->getSubject();

			$this->getStore()->updateData( $semanticData );
		}

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		return $query;
	}

	private function assertResultOrder( $expected, $results ) {
		$hasSameOrder = true;

		foreach ( $results as $key => $dataItem ) {
			if ( $expected[$key]->getHash() !== $dataItem->getHash() ) {
				$hasSameOrder = false;
			}
		}

		$this->assertTrue(
			$hasSameOrder,
			'Failed asserting that results have expected order.'
		);
	}

}
