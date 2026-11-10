<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Config\HashConfig;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\ParsoidUse;

/**
 * @covers \SMW\MediaWiki\ParsoidUse
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class ParsoidUseTest extends TestCase {

	public function testMediaWikiWithoutTheSettingsDoesNotUseParsoid() {
		$parsoidUse = new ParsoidUse( new HashConfig( [] ) );

		$this->assertFalse( $parsoidUse->forRendering() );
		$this->assertFalse( $parsoidUse->forLinksUpdates() );
	}

	public function testLinksUpdatesFollowTheParserSettingByDefault() {
		$parsoidUse = new ParsoidUse( new HashConfig( [ 'UseParsoidParser' => true, 'UseParsoidLinksUpdate' => null ] ) );

		$this->assertTrue( $parsoidUse->forRendering() );
		$this->assertTrue( $parsoidUse->forLinksUpdates() );
	}

	public function testLinksUpdatesCanUseTheLegacyParserWhileParsoidRendersPages() {
		$parsoidUse = new ParsoidUse( new HashConfig( [ 'UseParsoidParser' => true, 'UseParsoidLinksUpdate' => false ] ) );

		$this->assertTrue( $parsoidUse->forRendering() );
		$this->assertFalse( $parsoidUse->forLinksUpdates() );
	}

	public function testLinksUpdatesCanUseParsoidWhileTheLegacyParserRendersPages() {
		$parsoidUse = new ParsoidUse( new HashConfig( [ 'UseParsoidParser' => false, 'UseParsoidLinksUpdate' => true ] ) );

		$this->assertFalse( $parsoidUse->forRendering() );
		$this->assertTrue( $parsoidUse->forLinksUpdates() );
	}

	public function testTheLegacyParserSettingAlsoAppliesToLinksUpdatesByDefault() {
		$parsoidUse = new ParsoidUse( new HashConfig( [ 'UseParsoidParser' => false, 'UseParsoidLinksUpdate' => null ] ) );

		$this->assertFalse( $parsoidUse->forRendering() );
		$this->assertFalse( $parsoidUse->forLinksUpdates() );
	}

}
