<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExploreListBuilderTest extends TestCase {

	private $profile;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExploreListBuilder::class,
			new ExploreListBuilder( $this->profile )
		);
	}

	public function testScriptBearingLabelIsEscapedInExploreList() {
		$html = $this->buildExploreListForLabel( '<img src=x onerror=alert(1)>' );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)', $html );
	}

	public function testBenignLabelIsPreservedInExploreList() {
		$html = $this->buildExploreListForLabel( 'Books & more' );

		$this->assertStringContainsString( '>Books &amp; more</a>', $html );
	}

	/**
	 * Renders the FacetedSearch explore list for a single exploration link
	 * carrying the given label and returns the produced HTML.
	 */
	private function buildExploreListForLabel( string $label ): string {
		$this->profile->method( 'get' )->willReturn(
			[
				[
					'query' => '[[Modification date::+]]',
					'label' => $label,
				],
			]
		);
		$this->profile->method( 'getProfileName' )->willReturn( 'default' );

		$messageLocalizer = $this->createMock( MessageLocalizer::class );
		$messageLocalizer->method( 'msg' )->willReturn( '' );

		$builder = new ExploreListBuilder( $this->profile );
		$builder->setMessageLocalizer( $messageLocalizer );

		$title = $this->createMock( Title::class );
		$title->method( 'getLocalUrl' )->willReturn( '' );

		return $builder->buildHTML( $title );
	}

}
