<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;
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
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->mwHooksHandler->register(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'InternalParseBeforeLinks' )
		);
	}

	protected function tearDown() {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testNonParseForInvokedMessageParse() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$this->applicationFactory->registerObject( 'ParserData', $parserData );

		wfMessage( 'properties' )->parse();
	}

}
