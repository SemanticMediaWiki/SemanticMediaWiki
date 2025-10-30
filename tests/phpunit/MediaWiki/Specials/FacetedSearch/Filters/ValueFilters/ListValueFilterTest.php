<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\ListValueFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\ListValueFilter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ListValueFilterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateParser;
	private $urlArgs;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( '\MediaWiki\Html\TemplateParser' )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ListValueFilter::class,
			new ListValueFilter( $this->templateParser, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new ListValueFilter(
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
