<?php

namespace SMW\Tests\Integration;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class SemanticDataCountMapIntegrationTest extends SMWIntegrationTestCase {

	private $semanticDataFactory;
	private $mwHooksHandler;
	private $subjects = [];

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
	}

	protected function tearDown(): void {
		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->subjects );
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testCountMapFromDatabase() {
		$store = $this->getStore();

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$subject = $semanticData->getSubject();
		$property = new DIProperty( 'CountMap_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'CountMap_2' ),
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$fieldList = $store->getObjectIds()->preload( [ $subject ] );

		$this->assertEquals(
			[
				'CountMap_1' => 2,
				'CountMap_2' => 1
			],
			$fieldList->getCountListByType( $fieldList::PROPERTY_LIST )
		);

		$this->assertEquals(
			[
				'Count1' => 1
			],
			$fieldList->getCountListByType( $fieldList::CATEGORY_LIST )
		);

		$semanticData->removePropertyObjectValue(
			$property,
			new DIWikiPage( 'Count2', NS_MAIN )
		);

		$store->updateData( $semanticData );

		$fieldList = $store->getObjectIds()->preload( [ $subject ] );

		$this->assertEquals(
			[
				'CountMap_1' => 1,
				'CountMap_2' => 1
			],
			$fieldList->getCountListByType( $fieldList::PROPERTY_LIST )
		);

		$this->subjects[] = $semanticData->getSubject();
	}

}
