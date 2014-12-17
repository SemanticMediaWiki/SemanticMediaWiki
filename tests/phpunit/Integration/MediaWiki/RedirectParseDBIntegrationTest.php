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
 * @group mediawiki-databas
 *
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class RedirectParseDBIntegrationTest extends MwDBaseUnitTestCase {

	private $pageCreator;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->pageCreator = UtilityFactory::getInstance()->newPageCreator();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testRedirectParseUsingManualRedirect() {

		$target = Title::newFromText( 'RedirectParseUsingManualRedirect' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( '#REDIRECT [[RedirectParseUsingManualRedirect]]' );

		$expected = array(
			new DIProperty( '_REDI' )
		);

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( DIWikiPage::newFromTitle( $target ) )
		);
	}

	public function testRedirectParseUsingMoveToPage() {

		$target = Title::newFromText( 'RedirectParseUsingMoveToPage' ) ;

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) );

		$this->pageCreator
			->getPage()
			->getTitle()
			->moveTo( $target, false, 'test', true );

		$expected = array(
			new DIProperty( '_REDI' )
		);

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( DIWikiPage::newFromTitle( $target ) )
		);
	}

	public function testManualRemovalOfRedirectTarget() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$target  = DIWikiPage::newFromTitle( Title::newFromText( 'ManualRemovalOfRedirectTarget' ) );
		$target->getSortKey();

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( '#REDIRECT [[Property:ManualRemovalOfRedirectTarget-NotTheRealTarget]]' )
			->doEdit( '#REDIRECT [[ManualRemovalOfRedirectTarget]]' );

		$expected = array(
			new DIProperty( '_REDI' )
		);

		$this->assertEquals(
			$target,
			$this->getStore()->getRedirectTarget( $subject )
		);

		$this->semanticDataValidator->assertHasProperties(
			$expected,
			$this->getStore()->getInProperties( $target )
		);

		$this->pageCreator
			->createPage( $subject->getTitle() )
			->doEdit( 'removed redirect target' );

		$this->assertEquals(
			$subject,
			$this->getStore()->getRedirectTarget( $subject )
		);

		$this->assertEmpty(
			$this->getStore()->getInProperties( $target )
		);
	}

}
