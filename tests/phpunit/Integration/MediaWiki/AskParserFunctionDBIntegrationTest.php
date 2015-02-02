<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class AskParserFunctionDBIntegrationTest extends MwDBaseUnitTestCase {

	private $pageCreator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testAskToCreatePropertyAnnotationFromTranscludedTemplate() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->pageCreator
			->createPage( Title::newFromText( 'AskTemplateToAddPropertyAnnotation', NS_TEMPLATE ) )
			->doEdit( '<includeonly>{{#set:|SetPropertyByAskTemplate=1234}}</includeonly>' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ . '00' ) )
			->doEdit( '{{#set:|TestPropertyByAskTemplate=TestValueByAskTemplate}}' );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit(
				'{{#ask:[[TestPropertyByAskTemplate::TestValueByAskTemplate]]|link=none|sep=|template=AskTemplateToAddPropertyAnnotation}}' );

		$expected = array(
			'propertyCount' => 4,
			'propertyKeys'  => array( '_ASK', '_MDAT', '_SKEY', 'SetPropertyByAskTemplate' )
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}
