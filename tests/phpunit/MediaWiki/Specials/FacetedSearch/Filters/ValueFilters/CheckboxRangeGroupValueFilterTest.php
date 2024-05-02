<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxRangeGroupValueFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilters\CheckboxRangeGroupValueFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CheckboxRangeGroupValueFilterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $urlArgs;
	private $messageLocalizer;
	private $compartmentIterator;

	protected function setUp() : void {
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

		$this->compartmentIterator = $this->getMockBuilder( '\SMW\Schema\CompartmentIterator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CheckboxRangeGroupValueFilter::class,
			new CheckboxRangeGroupValueFilter( $this->templateEngine, $this->compartmentIterator, [] )
		);
	}

	public function testCreate_NoFilter() {

		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->will( $this->returnValue( '' ) );

		$params = [
			'min_item' => 1
		];

		$instance = new CheckboxRangeGroupValueFilter(
			$this->templateEngine,
			$this->compartmentIterator,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [];
		$raw = [];

		$this->assertInternalType(
			'string',
			$instance->create( $this->urlArgs, 'Foo', $filters, $raw )
		);
	}

}

