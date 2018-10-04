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

	protected function tearDown() {
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

}
