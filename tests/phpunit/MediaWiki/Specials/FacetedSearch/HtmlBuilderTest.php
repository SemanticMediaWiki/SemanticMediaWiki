<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\HtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class HtmlBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $profile;
	private $templateEngine;
	private $optionsBuilder;
	private $extraFieldBuilder;
	private $facetBuilder;
	private $resultFetcher;
	private $exploreListBuilder;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();

		$this->optionsBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\OptionsBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->extraFieldBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->facetBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->resultFetcher = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->exploreListBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\exploreListBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HtmlBuilder::class,
			new HtmlBuilder( $this->profile, $this->templateEngine, $this->optionsBuilder, $this->extraFieldBuilder, $this->facetBuilder, $this->resultFetcher, $this->exploreListBuilder )
		);
	}

}
