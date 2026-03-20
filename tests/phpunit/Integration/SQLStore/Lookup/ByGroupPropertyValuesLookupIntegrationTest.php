<?php

namespace SMW\Tests\Integration\SQLStore\Lookup;

use SMW\DataItems\Blob;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
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
class ByGroupPropertyValuesLookupIntegrationTest extends SMWIntegrationTestCase {

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

	public function testGroup_SingleSubject_Page() {
		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new Property( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( 'GroupCount_2' ),
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( '_INST' ),
			new WikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 1, 'Count2' => 1 ],
				'raw' => [ 'Count1' => 'Count1', 'Count2' => 'Count2' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new Property( 'GroupCount_1' ), $subjects )
		);

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 1 ],
				'raw' => [ 'Count1' => 'Count1' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new Property( 'GroupCount_2' ), $subjects )
		);
	}

	public function testGroup_SingleSubject_Blob() {
		$store = $this->getStore();
		$subjects = [];

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new Property( 'GroupBlobCount_1' );
		$property->setPropertyValueType( '_txt' );

		$semanticData->addPropertyObjectValue(
			$property,
			new Blob( 'BlobCount_1' )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new Blob( 'BlobCount_1' )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'BlobCount_1' => 1 ],
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
		$property = new Property( 'GroupNumCount_1' );
		$property->setPropertyValueType( '_num' );

		$semanticData->addPropertyObjectValue(
			$property,
			new Number( 12 )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new Number( 42 )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 12 => 1, 42 => 1 ],
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
		$property = new Property( 'GroupDateCount_1' );
		$property->setPropertyValueType( '_dat' );

		$semanticData->addPropertyObjectValue(
			$property,
			new Time( 1, 2000 )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 2000 => '1' ],
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
		$property = new Property( 'GroupUriCount_1' );
		$property->setPropertyValueType( '_uri' );

		$semanticData->addPropertyObjectValue(
			$property,
			Uri::doUnserialize( 'http://username@example.org/' )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'http://username@example.org/' => '1' ],
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
		$property = new Property( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( 'GroupCount_2' ),
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( '_INST' ),
			new WikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$semanticData = $this->semanticDataFactory
			->newEmptySemanticData( __METHOD__ . '-2' );

		$this->subjects[] = $semanticData->getSubject();

		$subjects[] = $semanticData->getSubject()->getSha1();
		$property = new Property( 'GroupCount_1' );

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			$property,
			new WikiPage( 'Count2', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( 'GroupCount_2' ),
			new WikiPage( 'Count1', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( 'GroupCount_2' ),
			new WikiPage( 'Count3', NS_MAIN )
		);

		$semanticData->addPropertyObjectValue(
			new Property( '_INST' ),
			new WikiPage( 'Count1', NS_CATEGORY )
		);

		$store->updateData( $semanticData );

		$byGroupPropertyValuesLookup = $store->service( 'ByGroupPropertyValuesLookup' );

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 2, 'Count2' => 2 ],
				'raw' => [ 'Count1' => 'Count1', 'Count2' => 'Count2' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new Property( 'GroupCount_1' ), $subjects )
		);

		$this->assertEquals(
			[
				'groups' => [ 'Count1' => 2, 'Count3' => 1 ],
				'raw' => [ 'Count1' => 'Count1', 'Count3' => 'Count3' ]
			],
			$byGroupPropertyValuesLookup->findValueGroups( new Property( 'GroupCount_2' ), $subjects )
		);
	}

}
