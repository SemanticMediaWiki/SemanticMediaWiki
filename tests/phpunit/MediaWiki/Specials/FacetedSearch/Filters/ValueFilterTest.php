<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultValueFilterNotFoundException;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilterFactory;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;
use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\ValueFilter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ValueFilterTest extends TestCase {

	private $templateParser;
	private $valueFilterFactory;
	private $schemaFinder;
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

		$this->valueFilterFactory = $this->getMockBuilder( ValueFilterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder = $this->getMockBuilder( SchemaFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( UrlArgs::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueFilter::class,
			new ValueFilter( $this->templateParser, $this->valueFilterFactory, $this->schemaFinder, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new ValueFilter(
			$this->templateParser,
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
		$schemaList = $this->getMockBuilder( SchemaList::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder->expects( $this->any() )
			->method( 'newSchemaList' )
			->willReturn( $schemaList );

		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'range_group_filter_preference' => false,
			'default_filter' => 'list_filter'
		];

		$instance = new ValueFilter(
			$this->templateParser,
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
		$schemaList = $this->getMockBuilder( SchemaList::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFinder->expects( $this->any() )
			->method( 'newSchemaList' )
			->willReturn( $schemaList );

		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'range_group_filter_preference' => false,
			'default_filter' => ''
		];

		$instance = new ValueFilter(
			$this->templateParser,
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

		$this->expectException( DefaultValueFilterNotFoundException::class );
		$instance->create( $this->urlArgs, $filters );
	}

}
