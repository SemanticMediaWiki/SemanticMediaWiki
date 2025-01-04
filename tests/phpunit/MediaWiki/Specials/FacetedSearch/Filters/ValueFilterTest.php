<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ValueFilterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $valueFilterFactory;
	private $schemaFinder;
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

		$this->valueFilterFactory = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueFilter::class,
			new ValueFilter( $this->templateEngine, $this->valueFilterFactory, $this->schemaFinder, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new ValueFilter(
			$this->templateEngine,
			$this->valueFilterFactory,
			$this->schemaFinder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [];

		$this->assertIsArray(

			$instance->create( $this->urlArgs, $filters )
		);
	}

	public function testCreate_OneFilter_ListFilter() {
		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder->expects( $this->any() )
			->method( 'newSchemaList' )
			->willReturn( $schemaList );

		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'range_group_filter_preference' => false,
			'default_filter' => 'list_filter'
		];

		$instance = new ValueFilter(
			$this->templateEngine,
			$this->valueFilterFactory,
			$this->schemaFinder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [
			'filter' => [ 'Foo' => [ 1, 42 ] ]
		];

		$this->assertIsArray(

			$instance->create( $this->urlArgs, $filters )
		);
	}

	public function testCreate_NoDefaultFilterThrowsException() {
		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder->expects( $this->any() )
			->method( 'newSchemaList' )
			->willReturn( $schemaList );

		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'range_group_filter_preference' => false,
			'default_filter' => ''
		];

		$instance = new ValueFilter(
			$this->templateEngine,
			$this->valueFilterFactory,
			$this->schemaFinder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [
			'filter' => [ 'Foo' => [ 1, 42 ] ]
		];

		$this->expectException( '\SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultValueFilterNotFoundException' );
		$instance->create( $this->urlArgs, $filters );
	}

}

