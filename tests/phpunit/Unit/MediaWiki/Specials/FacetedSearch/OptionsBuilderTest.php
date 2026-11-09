<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class OptionsBuilderTest extends TestCase {

	private $profile;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			OptionsBuilder::class,
			new OptionsBuilder( $this->profile )
		);
	}

	public function testProfilesEscapesAProfileKeyCarryingMarkup() {
		$this->profile->expects( $this->any() )
			->method( 'getProfileList' )
			->willReturn( [ "x'></select><svg/onload=alert(1)>_profile" => 'smw-facetedsearch-profile-label-x' ] );

		$this->profile->expects( $this->any() )
			->method( 'getProfileName' )
			->willReturn( '' );

		$html = ( new OptionsBuilder( $this->profile ) )->profiles();

		$this->assertStringNotContainsString( '</select><svg', $html );
		$this->assertStringContainsString( '&lt;/select&gt;&lt;svg', $html );
	}

	public function testProfilesSelectsOnlyTheCurrentProfileAndEscapesKeys() {
		// Keys sort to before < mid&val < zz, so the selected one sits in the middle.
		$this->profile->expects( $this->any() )
			->method( 'getProfileList' )
			->willReturn( [
				'before_profile' => 'smw-facetedsearch-profile-label-x',
				'mid&val_profile' => 'smw-facetedsearch-profile-label-x',
				'zz_profile' => 'smw-facetedsearch-profile-label-x',
			] );

		$this->profile->expects( $this->any() )
			->method( 'getProfileName' )
			->willReturn( 'mid&val' );

		$html = ( new OptionsBuilder( $this->profile ) )->profiles();

		// The current profile's key is escaped in the value attribute and is the
		// only selected option.
		$this->assertStringContainsString( '<option value="mid&amp;val" selected=""', $html );
		$this->assertStringNotContainsString( 'value="before" selected', $html );
		$this->assertStringNotContainsString( 'value="zz" selected', $html );
	}

}
