<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use SMW\DataModel\SemanticData;
use SMW\Services\ServicesFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\SMWDeclarativeHookReseater;

/**
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class ParserFirstCallInitIntegrationTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Disable every SMW declarative hook, then re-register only SMW's
		// ParserFirstCallInit. This matches the legacy "deregisterListedHooks
		// then re-register the one we care about" shape: other SMW handlers
		// (ParserAfterTidy etc.) must stay off so they cannot annotate the
		// parser output the assertion is checking.
		$reseater = new SMWDeclarativeHookReseater(
			MediaWikiServices::getInstance()->getHookContainer()
		);
		foreach ( $reseater->getDeclarativeHookNames() as $hook ) {
			$this->clearHook( $hook );
		}
		$this->setTemporaryHook(
			'ParserFirstCallInit',
			$reseater->buildSmwHandlerFor( 'ParserFirstCallInit' )
		);
	}

	/**
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionEnabled( $parserName, $text ) {
		$expectedNullOutputFor = [
			'concept',
			'declare'
		];

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );
		$this->testEnvironment->addConfiguration( 'smwgQEnabled', true );

		$instance = ServicesFactory::getInstance()->newContentParser( $title );
		$instance->parse( $text );

		if ( in_array( $parserName, $expectedNullOutputFor ) ) {
			return $this->assertNull(
				$this->findSemanticataFromOutput( $instance->getOutput() )
			);
		}

		$this->assertInstanceOf(
			SemanticData::class,
			$this->findSemanticataFromOutput( $instance->getOutput() )
		);
	}

	/**
	 * @dataProvider textToParseProvider
	 */
	public function testParseWithParserFunctionDisabled( $parserName, $text ) {
		$expectedNullOutputFor = [
			'concept',
			'declare',
			'ask',
			'show'
		];

		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );
		$this->testEnvironment->addConfiguration( 'smwgQEnabled', false );

		$instance = ServicesFactory::getInstance()->newContentParser( $title );
		$instance->parse( $text );

		if ( in_array( $parserName, $expectedNullOutputFor ) ) {
			return $this->assertNull(
				$this->findSemanticataFromOutput( $instance->getOutput() )
			);
		}

		$this->assertInstanceOf(
			SemanticData::class,
			$this->findSemanticataFromOutput( $instance->getOutput() )
		);
	}

	public function textToParseProvider() {
		$provider = [];

		# 0 ask
		$provider[] = [
			'ask',
			'{{#ask: [[Modification date::+]]|limit=1}}'
		];

		# 1 show
		$provider[] = [
			'show',
			'{{#show: [[Foo]]|limit=1}}'
		];

		# 2 subobject
		$provider[] = [
			'subobject',
			'{{#subobject:|foo=bar|lila=lula,linda,luna|+sep=,}}'
		];

		# 3 set
		$provider[] = [
			'set',
			'{{#set:|foo=bar|lila=lula,linda,luna|+sep=,}}'
		];

		# 4 set_recurring_event
		$provider[] = [
			'set_recurring_event',
			'{{#set_recurring_event:some more tests|property=has date|' .
			'has title=Some recurring title|title2|has group=Events123|Events456|start=June 8, 2010|end=June 8, 2011|' .
			'unit=week|period=1|limit=10|duration=7200|include=March 16, 2010;March 23, 2010|+sep=;|' .
			'exclude=March 15, 2010;March 22, 2010|+sep=;}}'
		];

		# 5 declare
		$provider[] = [
			'declare',
			'{{#declare:population=Foo}}'
		];

		# 6 concept
		$provider[] = [
			'concept',
			'{{#concept:[[Modification date::+]]|Foo}}'
		];

		return $provider;
	}

	private function findSemanticataFromOutput( $parserOutput ) {
		return $parserOutput->getExtensionData( 'smwdata' );
	}

}
