<?php

namespace SMW\Tests\Integration\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\Tests\DatabaseTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMW\DIWikiPage;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ByGroupPropertyValuesLookupIntegrationTest extends DatabaseTestCase {

	private $semanticDataFactory;
	private $subjects = [];

	protected function setUp() : void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();
	}

	protected function tearDown() : void {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $this->subjects );
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testGroup_SingleSubject_Page() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'GroupCount_2' ),
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 1, 'Count2' => 1 ] ,
				'raw' => [ 'Count1' => 'Count1', 'Count2' => 'Count2' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new DIProperty( 'GroupCount_1' ), $subjects )
		);

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 1 ] ,
				'raw' => [ 'Count1' => 'Count1' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new DIProperty( 'GroupCount_2' ), $subjects )
		);
	}

	public function testGroup_SingleSubject_Blob() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupBlobCount_1' );
		$property->setPropertyValueType( '_txt' );

		$semanticData->addPropertyObjectValue(
			$property,
			new \SMWDIBlob( 'BlobCount_1' )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new \SMWDIBlob( 'BlobCount_1' )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'BlobCount_1' => 1 ] ,
				'raw' => [ 'BlobCount_1' => 'BlobCount_1' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( $property, $subjects )
		);
	}

	public function testGroup_SingleSubject_Number() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupNumCount_1' );
		$property->setPropertyValueType( '_num' );

		$semanticData->addPropertyObjectValue(
			$property,
			new \SMWDINumber( 12 )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new \SMWDINumber( 42 )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 12 => 1, 42 => 1 ] ,
				'raw' => [ 12 => 12, 42 => '42' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( $property, $subjects )
		);
	}

	public function testGroup_SingleSubject_Date() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupDateCount_1' );
		$property->setPropertyValueType( '_dat' );

		$semanticData->addPropertyObjectValue(
			$property,
			new \SMWDITime( 1, 2000 )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 2000 => '1' ] ,
				'raw' => [ 2000 => '2000' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( $property, $subjects )
		);
	}

	public function testGroup_SingleSubject_Uri() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupUriCount_1' );
		$property->setPropertyValueType( '_uri' );

		$semanticData->addPropertyObjectValue(
			$property,
			\SMWDIUri::doUnserialize( 'http://username@example.org/' )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'http://username@example.org/' => '1' ] ,
				'raw' => [ 'http://username@example.org/' => 'http://username@example.org/' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( $property, $subjects )
		);
	}

	public function testGroup_MultiSubjects() {

		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-1' );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'GroupCount_2' ),
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-2' );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new DIProperty( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new DIWikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'GroupCount_2' ),
			new DIWikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( 'GroupCount_2' ),
			new DIWikiPage( 'Count3', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_INST' ),
			new DIWikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 2, 'Count2' => 2 ] ,
				'raw' => [ 'Count1' => 'Count1', 'Count2' => 'Count2' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new DIProperty( 'GroupCount_1' ), $subjects )
		);

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 2, 'Count3' => 1 ] ,
				'raw' => [ 'Count1' => 'Count1', 'Count3' => 'Count3' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new DIProperty( 'GroupCount_2' ), $subjects )
		);
	}

}
