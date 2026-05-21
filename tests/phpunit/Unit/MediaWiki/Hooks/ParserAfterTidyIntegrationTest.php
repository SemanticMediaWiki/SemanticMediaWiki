<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class ParserAfterTidyIntegrationTest extends TestCase {

	private $mwHooksHandler;
	private $parserAfterTidyHook;
	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$this->mwHooksHandler = UtilityFactory::getInstance()->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->mwHooksHandler->register(
			'ParserAfterTidy',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'ParserAfterTidy' )
		);
	}

	protected function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testNonParseForInvokedMessageParse() {
		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$parserData->expects( $this->never() )
			->method( 'getSemanticData' );

		$this->applicationFactory->registerObject( 'ParserData', $parserData );

		wfMessage( 'properties' )->parse();
	}

}
