<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FacetBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $profile;
	private $templateEngine;
	private $filterFactory;
	private $resultFetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();

		$this->filterFactory = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\FilterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->resultFetcher = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FacetBuilder::class,
			new FacetBuilder( $this->profile, $this->templateEngine, $this->filterFactory, $this->resultFetcher )
		);
	}

}
