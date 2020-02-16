<?php

namespace SMW\Tests\Integration\SQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\PageCreator;
use SMW\Tests\Utils\PageDeleter;
use SMW\Tests\Utils\Validators\SemanticDataValidator;
use Title;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class SubSemanticDataDBIntegrationTest extends MwDBaseUnitTestCase {

	private $title;

	protected function tearDown() : void {
		$pageDeleter= new PageDeleter();

		$pageDeleter->deletePage( $this->title );

		parent::tearDown();
	}

	public function testCreatePageWithSubobject() {

		$this->title = Title::newFromText( __METHOD__ );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'{{#subobject:namedSubobject|AA=Test1|@sortkey=Z}}' .
				'{{#subobject:|BB=Test2|@sortkey=Z}}' );

		$semanticData = $this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->title ) );

		$this->assertInstanceOf(
			'SMW\SemanticData',
			$semanticData->findSubSemanticData( 'namedSubobject' )
		);

		$expected = [
			'propertyCount'  => 2,
			'properties' => [
				new DIProperty( 'AA' ),
				new DIProperty( 'BB' ),
				new DIProperty( '_SKEY' )
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

		$this->title = Title::newFromText( 'Display_precision_of', SMW_NS_PROPERTY );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'[[Property description::Simple monolingual test@en]]'
			);

		$semanticData = $this->getStore()->getSemanticData(
			DIWikiPage::newFromTitle( $this->title )
		);

		$expected = [
			'propertyCount'  => 3,
			'properties' => [
				new DIProperty( '_TEXT' ),
				new DIProperty( '_LCODE' ),
				new DIProperty( '_SKEY' )
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

		$this->title = Title::newFromText( 'Display_precision_of', SMW_NS_PROPERTY );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $this->title )
			->doEdit(
				'[[Property description::Simple monolingual test@en]]'
			);

		// 1) SMW\Tests\Integration\SQLStore\SubSemanticDataDBIntegrationTest::testPredefinedProperty_Key_MonolingualText
		// SMW\Exception\SubSemanticDataException: Data for a subobject of Display_precision_of cannot be added to _PREC.
		//
		// ...\SemanticMediaWiki\src\DataModel\SubSemanticData.php:206
		// ...\SemanticMediaWiki\includes\SemanticData.php:814
		// ...\SemanticMediaWiki\src\SQLStore\EntityStore\StubSemanticData.php:417
		// ...\SemanticMediaWiki\src\SQLStore\EntityStore\StubSemanticData.php:202
		// ...\SemanticMediaWiki\tests\phpunit\Integration\SQLStore\SubSemanticDataDBIntegrationTest.php:153
		// ...\SemanticMediaWiki\tests\phpunit\DatabaseTestCase.php:155
		// ...\doMaintenance.php:94

		$semanticData = $this->getStore()->getSemanticData(
			DIWikiPage::newFromText( '_PREC', SMW_NS_PROPERTY )
		);

		$expected = [
			'propertyCount'  => 3,
			'properties' => [
				new DIProperty( '_TEXT' ),
				new DIProperty( '_LCODE' ),
				new DIProperty( '_SKEY' )
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
