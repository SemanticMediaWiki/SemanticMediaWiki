<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\FacetedSearch\ExploreListBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;
use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class HtmlBuilderTest extends TestCase {

	private $profile;
	private TemplateParser $templateParser;
	private $optionsBuilder;
	private $extraFieldBuilder;
	private $facetBuilder;
	private $resultFetcher;
	private $exploreListBuilder;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = new TemplateParser( __DIR__ . '/../../../../../../templates/FacetedSearch' );

		$this->optionsBuilder = $this->getMockBuilder( OptionsBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->extraFieldBuilder = $this->getMockBuilder( ExtraFieldBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->facetBuilder = $this->getMockBuilder( FacetBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->resultFetcher = $this->getMockBuilder( ResultFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->exploreListBuilder = $this->getMockBuilder( ExploreListBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer->method( 'msg' )->willReturn( '' );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HtmlBuilder::class,
			new HtmlBuilder( $this->profile, $this->templateParser, $this->optionsBuilder, $this->extraFieldBuilder, $this->facetBuilder, $this->resultFetcher, $this->exploreListBuilder )
		);
	}

	public function testMaliciousCardStateValueIsEscapedInHiddenInput() {
		$html = $this->buildHtmlForCardState( [ '0' => '"><script>alert(1)</script>' ] );

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;', $html );
	}

	public function testMaliciousCardStateKeyIsEscapedInHiddenInput() {
		$html = $this->buildHtmlForCardState( [ '"><script>alert(2)</script>' => 'e' ] );

		$this->assertStringNotContainsString( '<script>alert(2)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(2)&lt;/script&gt;', $html );
	}

	public function testBenignCardStateIsPreservedInHiddenInput() {
		$html = $this->buildHtmlForCardState( [ 'extra-fields' => 'c' ] );

		$this->assertStringContainsString( 'name="cstate[extra-fields]"', $html );
		$this->assertStringContainsString( 'value="c"', $html );
	}

	public function testNonScalarCardStateValueIsIgnored() {
		$html = $this->buildHtmlForCardState( [ 'k' => [ 'b' => 'x' ] ] );

		$this->assertStringNotContainsString( 'cstate[k]', $html );
	}

	/**
	 * Renders the full search page for a given "cstate" (card state) request
	 * value and returns the produced HTML.
	 */
	private function buildHtmlForCardState( array $cstate ): string {
		$this->resultFetcher->method( 'getHtml' )->willReturn( '' );
		$this->resultFetcher->method( 'getTotalCount' )->willReturn( 0 );
		$this->resultFetcher->method( 'getOffset' )->willReturn( 0 );
		$this->resultFetcher->method( 'getLimit' )->willReturn( 10 );
		$this->resultFetcher->method( 'hasFurtherResults' )->willReturn( false );

		$title = $this->createMock( Title::class );
		$title->method( 'getLocalUrl' )->willReturn( '' );

		$instance = new HtmlBuilder(
			$this->profile,
			$this->templateParser,
			$this->optionsBuilder,
			$this->extraFieldBuilder,
			$this->facetBuilder,
			$this->resultFetcher,
			$this->exploreListBuilder
		);

		$instance->setMessageLocalizer( $this->messageLocalizer );

		return $instance->buildHTML( $title, new UrlArgs( [ 'q' => 'Text', 'cstate' => $cstate ] ) );
	}

}
