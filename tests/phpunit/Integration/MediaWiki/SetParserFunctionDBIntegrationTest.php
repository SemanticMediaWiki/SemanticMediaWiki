<?php

namespace SMW\Tests\Integration\MediaWiki;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use SMW\DIWikiPage;
use SMW\DIProperty;

use Title;

/**
 * @group semantic-mediawiki-integration
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.2
 *
 * @author mwjames
 */
class SetParserFunctionDBIntegrationTest extends MwDBaseUnitTestCase {

	private $pageCreator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testSetToTranscludeTemplateForValidPropertyValue() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->pageCreator
			->createPage( Title::newFromText( 'SetParserTemplateToCreateAskLink', NS_TEMPLATE ) )
			->doEdit( '{{#ask: [[{{{property}}}::{{{value}}}]]|limit=0|searchlabel={{{value}}} }}' );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit(
				'{{#set:SetParserTemplateProperty=SetParserTemplate1|+sep=;|template=SetParserTemplateToCreateAskLink}}' );

		$expected = array(
			'propertyCount' => 4,
			'propertyKeys'  => array( '_ASK', '_MDAT', '_SKEY', 'SetParserTemplateProperty' )
		);

		$semanticData = $this->getStore()->getSemanticData( $subject );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

	public function testTryToTranscludeTemplateForInvalidPropertyValue() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit(
				'{{#set:Modification date=NoTemplateForInvalidValue|+sep=;|template=NoTranscludedTemplateForInvalidValue}}' );

		$expected = array(
			'propertyCount' => 3,
			'propertyKeys'  => array( '_MDAT', '_SKEY', '_ERRP' )
		);

		$semanticData = $this->getStore()->getSemanticData( $subject );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$semanticData
		);
	}

}
