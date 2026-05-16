<?php

namespace SMW\Tests\Integration;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use SMW\ParserData;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @covers \SMW\ParserData
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author mwjames
 */
class ParserDataIntegrationTest extends SMWIntegrationTestCase {

	/**
	 * Exercises real ParserOutput/ParserOptions instead of mocks to confirm
	 * that `$smwgSetParserCacheKeys` governs the actual parser cache key: a
	 * disabled key must leave the cache key untouched.
	 */
	public function testAddExtraParserKeyParserCacheEffectFollowsConfiguration() {
		$title = Title::newFromText( __FUNCTION__ );

		// Enabled: the key is recorded as a used parser option, which is what
		// makes MediaWiki vary the parser cache by the option value.
		$this->testEnvironment->withConfiguration( [ 'smwgSetParserCacheKeys' => [ 'userlang' ] ] );

		$enabledOutput = new ParserOutput();
		$enabled = new ParserData( $title, $enabledOutput );
		$enabled->setParserOptions( ParserOptions::newFromAnon() );
		$enabled->addExtraParserKey( 'userlang' );

		$this->assertContains( 'userlang', $enabledOutput->getUsedOptions() );

		// Disabled: the key must neither be recorded as a used option nor leak
		// into the cache key through ParserOptions::addExtraKey().
		$this->testEnvironment->withConfiguration( [ 'smwgSetParserCacheKeys' => [] ] );

		$disabledOutput = new ParserOutput();
		$disabledOptions = ParserOptions::newFromAnon();
		$disabled = new ParserData( $title, $disabledOutput );
		$disabled->setParserOptions( $disabledOptions );

		$hashBefore = $disabledOptions->optionsHash( $disabledOutput->getUsedOptions(), $title );
		$disabled->addExtraParserKey( 'userlang' );

		$this->assertNotContains( 'userlang', $disabledOutput->getUsedOptions() );
		$this->assertSame(
			$hashBefore,
			$disabledOptions->optionsHash( $disabledOutput->getUsedOptions(), $title )
		);
	}

}
