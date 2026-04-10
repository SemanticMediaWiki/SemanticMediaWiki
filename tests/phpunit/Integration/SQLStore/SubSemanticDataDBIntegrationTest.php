<?php

namespace SMW\Tests\Integration\SQLStore;

use MediaWiki\MediaWikiServices;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Validators\SemanticDataValidator;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class SubSemanticDataDBIntegrationTest extends SMWIntegrationTestCase {

	private $title;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();
		$utilityFactory->newMwHooksHandler()
			->deregisterListedHooks()
			->invokeHooksFromRegistry();
	}

	protected function tearDown(): void {
		$pageDeleter = new PageDeleter();

		parent::tearDown();
	}

	public function testCreatePageWithSubobject() {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'{{#subobject:namedSubobject|AA=Test1|@sortkey=Z}}' .
				'{{#subobject:|BB=Test2|@sortkey=Z}}' );

		$semanticData = $this->getStore()->getSemanticData( WikiPage::newFromTitle( $this->title ) );

		$this->assertInstanceOf(
			SemanticData::class,
			$semanticData->findSubSemanticData( 'namedSubobject' )
		);

		$expected = [
			'propertyCount'  => 2,
			'properties' => [
				new Property( 'AA' ),
				new Property( 'BB' ),
				new Property( '_SKEY' )
			],
			'propertyValues' => [
				'Test1',
				'Test2',
				'Z'
			]
		];

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$subSemanticData
			);
		}
	}

	public function testPredefinedProperty_Canonical_MonolingualText() {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Display_precision_of', SMW_NS_PROPERTY );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'[[Property description::Simple monolingual test@en]]'
			);

		$semanticData = $this->getStore()->getSemanticData(
			WikiPage::newFromTitle( $this->title )
		);

		$expected = [
			'propertyCount'  => 3,
			'properties' => [
				new Property( '_TEXT' ),
				new Property( '_LCODE' ),
				new Property( '_SKEY' )
			],
			'propertyValues' => [
				'en',
				'Simple monolingual test',
				'Display precision of',
				'Display precision of#Simple monolingual test;en'
			]
		];

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$subSemanticData
			);
		}
	}

	public function testPredefinedProperty_Key_MonolingualText() {
		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Display_precision_of', SMW_NS_PROPERTY );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'[[Property description::Simple monolingual test@en]]'
			);

		$semanticData = $this->getStore()->getSemanticData(
			WikiPage::newFromText( '_PREC', SMW_NS_PROPERTY )
		);

		$expected = [
			'propertyCount'  => 3,
			'properties' => [
				new Property( '_TEXT' ),
				new Property( '_LCODE' ),
				new Property( '_SKEY' )
			],
			'propertyValues' => [
				'en',
				'Simple monolingual test',
				'Display precision of',
				'Display precision of#Simple monolingual test;en'
			]
		];

		$semanticDataValidator = new SemanticDataValidator();

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$subSemanticData
			);
		}
	}

}
