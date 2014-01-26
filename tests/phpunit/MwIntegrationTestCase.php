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
