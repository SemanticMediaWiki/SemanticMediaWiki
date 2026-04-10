<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter;
use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class CheckboxValueFilterTest extends TestCase {

	private $templateParser;
	private $urlArgs;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( UrlArgs::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CheckboxValueFilter::class,
			new CheckboxValueFilter( $this->templateParser, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new CheckboxValueFilter(
			$this->templateParser,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [];
		$raw = [];

		$this->assertIsString(

			$instance->create( $this->urlArgs, 'Foo', $filters, $raw )
		);
	}

}
