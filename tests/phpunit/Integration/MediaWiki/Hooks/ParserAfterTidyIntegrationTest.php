<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use SMW\ParserData;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class ParserAfterTidyIntegrationTest extends SMWIntegrationTestCase {

	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$reseater = new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		);
		foreach ( $reseater->getDeclarativeHookNames() as $hook ) {
			$this->clearHook( $hook );
		}
		$this->setTemporaryHook(
			'ParserAfterTidy',
			$reseater->buildSmwHandlerFor( 'ParserAfterTidy' )
		);
	}

	protected function tearDown(): void {
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
