<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   2.1
 *
 * @author mwjames
 */
class ParserAfterTidyIntegrationTest extends \PHPUnit_Framework_TestCase {

	private $mwHooksHandler;
	private $parserAfterTidyHook;
	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->mwHooksHandler->register(
			'ParserAfterTidy',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'ParserAfterTidy' )
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
