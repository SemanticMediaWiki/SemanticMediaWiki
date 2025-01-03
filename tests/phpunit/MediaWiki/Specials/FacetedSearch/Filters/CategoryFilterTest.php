<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\CategoryFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\CategoryFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CategoryFilterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $treeBuilder;
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

		$this->treeBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( '\SMW\Utils\UrlArgs' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CategoryFilter::class,
			new CategoryFilter( $this->templateEngine, $this->treeBuilder, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new CategoryFilter(
			$this->templateEngine,
			$this->treeBuilder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [];

		$this->assertIsString(

			$instance->create( $this->urlArgs, $filters )
		);
	}

	public function testCreate_OneFilter() {
		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'hierarchy_tree' => false
		];

		$instance = new CategoryFilter(
			$this->templateEngine,
			$this->treeBuilder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [
			'Foo' => 42
		];

		$this->assertIsString(

			$instance->create( $this->urlArgs, $filters )
		);
	}

}

