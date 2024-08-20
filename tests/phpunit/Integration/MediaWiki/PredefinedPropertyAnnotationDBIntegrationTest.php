<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\UtilityFactory;
use UnexpectedValueException;
use SMWDITime as DITime;
use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group Database
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.0
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotationDBIntegrationTest extends SMWIntegrationTestCase {

	private $semanticDataValidator;
	private $applicationFactory;
	private $dataValueFactory;
	private $mwHooksHandler;
	private $page;

	protected function setUp(): void {
		parent::setUp();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();

		$this->mwHooksHandler
			->deregisterListedHooks()
			->invokeHooksFromRegistry();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testPredefinedModificationDatePropertyAndChangedDefaultsortForNewPage() {
		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', [ '_MDAT' ] );

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$this->page = parent::getNonexistingTestPage( $title );
		parent::editPage( $this->page, '{{DEFAULTSORT:SortForFoo}}' );

		$dvPageModificationTime = $this->dataValueFactory->newDataValueByItem(
			DITime::newFromTimestamp( $this->getPage()->getTimestamp() )
		);

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_MDAT', '_SKEY' ],
			'propertyValues' => [ $dvPageModificationTime->getISO8601Date(), 'SortForFoo' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	public function testChangedDefaultsortWithoutPredefinedPropertiesForNewPage() {
		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', [] );

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$this->page = parent::getExistingTestPage( $title );

		$expected = [
			'propertyCount'  => 1,
			'propertyKeys'   => [ '_SKEY', '_INST' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	public function testAddedCategoryAndChangedDefaultsortWithoutPredefinedPropertiesForNewPage() {
		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', [] );

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$this->page = parent::getExistingTestPage( $title );
		parent::editPage( $this->page, '[[Category:SingleCategory]] {{DEFAULTSORT:SortForFoo}}' );

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_INST', '_SKEY' ],
			'propertyValues' => [ 'Category:SingleCategory', 'SortForFoo' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	/**
	 * @since 1.9.1
	 *
	 * @return \WikiPage
	 * @throws UnexpectedValueException
	 */
	public function getPage() {
		if ( $this->page instanceof \WikiPage ) {
			return $this->page;
		}

		throw new UnexpectedValueException( 'Expected a WikiPage instance, use createPage first' );
	}
}
