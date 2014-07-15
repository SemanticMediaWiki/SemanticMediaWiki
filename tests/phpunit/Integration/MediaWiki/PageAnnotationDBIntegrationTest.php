<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\PageCreator;
use SMW\Tests\MwDBaseUnitTestCase;

use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Application;

use SMWDITime as DITime;

use Title;

/**
 * @ingroup Test
 *
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
class PageAnnotationDBIntegrationTest extends MwDBaseUnitTestCase {

	private $semanticDataValidator;
	private $application;
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = new SemanticDataValidator();
		$this->application = Application::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCreatePage() {

		$title   = Title::newFromText( __METHOD__ );
		$subject = DIWikiPage::newFromTitle( $title );

		$pageCreator = new PageCreator();

		$pageCreator
			->createPage( $title )
			->doEdit( '{{DEFAULTSORT:SortForFoo}}' );

		$dvPageModificationTime = $this->dataValueFactory->newDataItemValue(
			DITime::newFromTimestamp( $pageCreator->getPage()->getTimestamp() )
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

}
