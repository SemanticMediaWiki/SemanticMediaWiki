<?php

namespace SMW\Tests\Integration\MediaWiki\Specials;

use MediaWiki\Request\FauxRequest;
use SMW\MediaWiki\Specials\SpecialAsk;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Tests\SpecialPageTestCase;
use SMW\Tests\TestEnvironment;

/**
 * Drives Special:Ask's remote-request (request_type) response wiring end to end and asserts the
 * content type each branch serves: the file-export and raw branches carry attacker-influenced
 * content (reflected separators, stored property values) and must not be served as executable
 * HTML. Runs in the Integration suite because execute() drives a real query through the store.
 *
 * @covers \SMW\MediaWiki\Specials\SpecialAsk
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SpecialAskRemoteRequestResponseTest extends SpecialPageTestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgQEnabled' => true,
			'smwgRemoteReqFeatures' => [ 'send-response' ],
		] );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	protected function getInstance() {
		$applicationFactory = ApplicationFactory::getInstance();

		return new SpecialAsk(
			$applicationFactory->getQuerySourceFactory(),
			$applicationFactory->getSettings()
		);
	}

	public function testExportSpecialPageResponseIsServedAsPlainText() {
		$this->executeRemoteRequest( 'special_page', 'dsv', [ 'separator' => '"><script>alert(1)</script>' ] );

		$this->assertSame( 'text/plain; charset=UTF-8', $this->getResponse()->getHeader( 'Content-Type' ) );
		$this->assertSame( 'nosniff', $this->getResponse()->getHeader( 'X-Content-Type-Options' ) );
	}

	public function testRawResponseIsServedAsPlainText() {
		$this->executeRemoteRequest( 'raw', 'list', [] );

		$this->assertSame( 'text/plain; charset=UTF-8', $this->getResponse()->getHeader( 'Content-Type' ) );
		$this->assertSame( 'nosniff', $this->getResponse()->getHeader( 'X-Content-Type-Options' ) );
	}

	public function testEmbedResponseIsNotForcedToPlainText() {
		$this->executeRemoteRequest( 'embed', 'list', [] );

		$this->assertNotSame( 'text/plain; charset=UTF-8', $this->getResponse()->getHeader( 'Content-Type' ) );
	}

	private function executeRemoteRequest( string $requestType, string $format, array $extraParams ) {
		$this->execute( '', new FauxRequest( [
			'q' => '[[Modification date::+]]',
			'po' => '?Modification date',
			'p' => [ 'format' => $format ] + $extraParams,
			'request_type' => $requestType,
		] ) );
	}
}
