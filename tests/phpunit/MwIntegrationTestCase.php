<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Setup;

use Title;
use UnexpectedValueException;

/**
 * This TestCase should only be used in case a real Database integration with
 * MediaWiki is under test
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.1
 *
 * @author mwjames
 */
abstract class MwIntegrationTestCase extends \MediaWikiTestCase {

	/** @var array */
	private $hooks = array();

	protected function setUp() {
		$this->removeFunctionHookRegistrationFromGlobal();
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
		$this->restoreFuntionHookRegistrationToGlobal();
	}

	/**
	 * In order for the test not being influenced by an exisiting setup
	 * registration we temporary remove from the GLOBALS configuration
	 * in order to enable hook and context assignment freely during testing
	 */
	protected function removeFunctionHookRegistrationFromGlobal() {
		$this->hooks = $GLOBALS['wgHooks'];
		$GLOBALS['wgHooks'] = array();
	}

	protected function restoreFuntionHookRegistrationToGlobal() {
		$GLOBALS['wgHooks'] = $this->hooks;
	}

	protected function runExtensionSetup( $context, $directory = 'Foo' ) {
		$setup = new Setup( $GLOBALS, $directory, $context );
		$setup->run();
	}

	/**
	 * Ensure that the SemanticData container is really empty and not filled
	 * with a single "_SKEY" property
	 */
	protected function evaluateSemanticDataIsEmpty( SemanticData $semanticData ) {

		$property = new DIProperty( '_SKEY' );

		foreach( $semanticData->getPropertyValues( $property ) as $dataItem ) {
			$semanticData->removePropertyObjectValue( $property, $dataItem );
		}

		return $semanticData->isEmpty();
	}

	protected function assertSemanticDataIsEmpty( SemanticData $semanticData ) {
		$this->assertTrue(
			$this->evaluateSemanticDataIsEmpty( $semanticData ),
			'Asserts that the SemanticData container is empty'
		);
	}

	protected function assertSemanticDataIsNotEmpty( SemanticData $semanticData ) {
		$this->assertFalse(
			$this->evaluateSemanticDataIsEmpty( $semanticData ),
			'Asserts that the SemanticData container is not empty'
		);
	}

	protected function getStore() {
		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable for SMWSQLStore3' );
		}

		return $store;
	}

	protected function createPage( Title $title, $editContent = '' ) {
		$pageCreator = new PageCreator();
		$pageCreator->createPage( $title, $editContent );
	}

	protected function deletePage( Title $title ) {
		$pageCreator = new PageDeleter();
		$pageCreator->deletePage( $title );
	}

}

class PageCreator {

	/** @var WikiPage */
	protected $page = null;

	/**
	 * @since 1.9.0.3
	 *
	 * @return WikiPage
	 * @throws UnexpectedValueException
	 */
	public function getPage() {

		if ( $this->page instanceof \WikiPage ) {
			return $this->page;
		}

		throw new UnexpectedValueException( 'Expected a WikiPage instance, use createPage first' );
	}

	/**
	 * @since 1.9.0.3
	 *
	 * @return PageCreator
	 */
	public function createPage( Title $title, $editContent = '' ) {

		$this->page = new \WikiPage( $title );

		$pageContent = 'Content of ' . $title->getFullText() . ' ' . $editContent;
		$editMessage = 'SMW system test: create page';

		return $this->doEdit( $pageContent, $editMessage );
	}

	/**
	 * @since 1.9.0.3
	 *
	 * @return PageCreator
	 */
	public function doEdit( $pageContent = '', $editMessage = '' ) {

		if ( class_exists( 'WikitextContent' ) ) {
			$content = new \WikitextContent( $pageContent );

			$this->getPage()->doEditContent(
				$content,
				$editMessage
			);

		} else {
			$this->getPage()->doEdit( $pageContent, $editMessage );
		}

		return $this;
	}

}

class PageDeleter {

	public function deletePage( Title $title ) {
		$page = new \WikiPage( $title );
		$page->doDeleteArticle( 'SMW system test: delete page' );
	}

}
