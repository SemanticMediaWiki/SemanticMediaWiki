<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\FilterFactory;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;
use SMW\MediaWiki\Specials\FacetedSearch\ResultFetcher;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\FacetBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FacetBuilderTest extends TestCase {

	private $profile;
	private $templateParser;
	private $filterFactory;
	private $resultFetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->filterFactory = $this->getMockBuilder( FilterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->resultFetcher = $this->getMockBuilder( ResultFetcher::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FacetBuilder::class,
			new FacetBuilder( $this->profile, $this->templateParser, $this->filterFactory, $this->resultFetcher )
		);
	}

}
