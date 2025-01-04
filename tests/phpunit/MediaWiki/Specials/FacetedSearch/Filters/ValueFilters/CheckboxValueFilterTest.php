<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxValueFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CheckboxValueFilterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $urlArgs;
	private $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CheckboxValueFilter::class,
			new CheckboxValueFilter( $this->templateEngine, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new CheckboxValueFilter(
			$this->templateEngine,
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

