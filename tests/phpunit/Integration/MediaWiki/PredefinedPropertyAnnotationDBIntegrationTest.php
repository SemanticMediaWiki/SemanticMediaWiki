<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;
use SMWDITime as DITime;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotationDBIntegrationTest extends MwDBaseUnitTestCase {

	private $semanticDataValidator;
	private $applicationFactory;
	private $dataValueFactory;
	private $mwHooksHandler;
	private $pageCreator;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testPredefinedModificationDatePropertyAndChangedDefaultsortForNewPage() {

		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', array( '_MDAT' ) );

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$this->pageCreator
			->createPage( $title, '{{DEFAULTSORT:SortForFoo}}' );

		$dvPageModificationTime = $this->dataValueFactory->newDataValueByItem(
			DITime::newFromTimestamp( $this->pageCreator->getPage()->getTimestamp() )
		);

		$expected = array(
			'propertyCount'  => 2,
			'propertyKeys'   => array( '_MDAT', '_SKEY' ),
			'propertyValues' => array( $dvPageModificationTime->getISO8601Date(), 'SortForFoo' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	public function testAddedCategoryAndChangedDefaultsortWithoutPredefinedPropertiesForNewPage() {

		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', array() );

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$this->pageCreator
			->createPage( $title )
			->doEdit( '{{DEFAULTSORT:SortForFoo}} [[Category:SingleCategory]]' );

		$expected = array(
			'propertyCount'  => 2,
			'propertyKeys'   => array( '_SKEY', '_INST' ),
			'propertyValues' => array( 'SortForFoo', 'SingleCategory' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}
