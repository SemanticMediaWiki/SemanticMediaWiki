<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialURIResolver;
use SMW\Tests\TestEnvironment;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialURIResolver
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SpecialURIResolverTest extends TestCase {

	private $testEnvironment;
	private $stringValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testExecuteOnEmptyContext() {
		$instance = new SpecialURIResolver();

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialURIResolver' )
		);

		$instance->execute( '' );

		$this->assertStringContainsString(
			'https://www.w3.org/2001/tag/issues.html#httpRange-14',
			$instance->getOutput()->getHTML()
		);
	}

	private function serverHost(): string {
		$urlUtils = MediaWikiServices::getInstance()->getUrlUtils();

		return $urlUtils->parse( (string)$urlUtils->expand( '/', PROTO_CURRENT ) )['host'] ?? '';
	}

	public function testIsLocalRedirectTargetAllowsSameHost() {
		$instance = TestingAccessWrapper::newFromObject( new SpecialURIResolver() );

		$this->assertTrue(
			$instance->isLocalRedirectTarget( 'http://' . $this->serverHost() . '/index.php/Foo' )
		);
	}

	public function testIsLocalRedirectTargetRejectsDifferentHost() {
		$instance = TestingAccessWrapper::newFromObject( new SpecialURIResolver() );

		$this->assertFalse(
			$instance->isLocalRedirectTarget( 'https://evil.example/index.php/Foo' )
		);
	}

	public function testIsLocalRedirectTargetRejectsProtocolRelativeOffHost() {
		$instance = TestingAccessWrapper::newFromObject( new SpecialURIResolver() );

		$this->assertFalse(
			$instance->isLocalRedirectTarget( '//evil.example/index.php/Foo' )
		);
	}

	public function testIsLocalRedirectTargetRejectsUnparsableUrl() {
		$instance = TestingAccessWrapper::newFromObject( new SpecialURIResolver() );

		$this->assertFalse(
			$instance->isLocalRedirectTarget( 'http://' )
		);
	}

	public function testIsLocalRedirectTargetAllowsMixedCaseHost() {
		$instance = TestingAccessWrapper::newFromObject( new SpecialURIResolver() );

		$this->assertTrue(
			$instance->isLocalRedirectTarget( 'http://' . strtoupper( $this->serverHost() ) . '/x' )
		);
	}

}
