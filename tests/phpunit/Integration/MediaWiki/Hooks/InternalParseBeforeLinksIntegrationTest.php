<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\Tests\Util\UtilityFactory;

use SMW\Application;

use Title;

/**
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class InternalParseBeforeLinksIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $parserAfterTidyHook;
	private $application;

	protected function setUp() {
		parent::setUp();

		$this->application = Application::getInstance();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->mwHooksHandler->registerHook(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getDefinition( 'InternalParseBeforeLinks' )
		);
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->application->clear();

		parent::tearDown();
	}

	public function testNonParseForInvokedMessageParse() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$this->application->registerObject( 'ParserData', $parserData );

		wfMessage( 'properties' )->parse();
	}

}
