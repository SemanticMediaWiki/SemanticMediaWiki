<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;

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
	private $templateParser;
	private $optionsBuilder;
	private $extraFieldBuilder;
	private $facetBuilder;
	private $resultFetcher;
	private $exploreListBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();

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

		$this->exploreListBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\exploreListBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HtmlBuilder::class,
			new HtmlBuilder( $this->profile, $this->templateParser, $this->optionsBuilder, $this->extraFieldBuilder, $this->facetBuilder, $this->resultFetcher, $this->exploreListBuilder )
		);
	}

}
