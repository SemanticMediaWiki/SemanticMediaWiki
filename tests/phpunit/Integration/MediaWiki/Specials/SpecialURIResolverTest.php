<?php

namespace SMW\Tests\Integration\MediaWiki\Specials;

use MediaWiki\Interwiki\ClassicInterwikiLookup;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use SMW\MediaWiki\Specials\SpecialURIResolver;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialURIResolver
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialURIResolverTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Register an interwiki prefix whose target resolves off-host, so that
		// Title::getFullURL() produces a foreign redirect target. Overriding the
		// config (rather than resetting the lookup alone) rebuilds the title
		// parser so the prefix is recognised during parsing.
		$this->overrideConfigValue(
			MainConfigNames::InterwikiCache,
			ClassicInterwikiLookup::buildCdbHash( [
				[
					'iw_prefix' => 'offsitewiki',
					'iw_url' => 'https://evil.example/$1',
					'iw_api' => '',
					'iw_wikiid' => '',
					'iw_local' => 0,
					'iw_trans' => 0,
				],
			] )
		);
	}

	private function newInstance(): SpecialURIResolver {
		// A non-RDF Accept header keeps execution on the canonical-redirect path.
		$_SERVER['HTTP_ACCEPT'] = 'text/html';

		$instance = new SpecialURIResolver();
		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'Special:URIResolver' )
		);

		return $instance;
	}

	private function serverHost(): string {
		$urlUtils = MediaWikiServices::getInstance()->getUrlUtils();

		return $urlUtils->parse( (string)$urlUtils->expand( '/', PROTO_CURRENT ) )['host'] ?? '';
	}

	public function testExecuteRejectsOffHostInterwikiRedirect() {
		$instance = $this->newInstance();

		// Decodes to "offsitewiki:Foo"; getFullURL() resolves to the foreign
		// host https://evil.example/Foo.
		$instance->execute( 'offsitewiki-3AFoo' );

		$this->assertSame(
			'',
			$instance->getOutput()->getRedirect(),
			'an off-host interwiki target must not be redirected to'
		);
	}

	public function testExecuteRedirectsLocalTitleOnHost() {
		$instance = $this->newInstance();

		$instance->execute( 'Foo' );

		$redirect = $instance->getOutput()->getRedirect();

		$this->assertNotSame( '', $redirect, 'a local title must still redirect' );
		$this->assertStringContainsString( $this->serverHost(), $redirect );
	}

}
