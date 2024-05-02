<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyFilterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $treeBuilder;
	private $urlArgs;
	private $messageLocalizer;

	protected function setUp() : void {
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
			PropertyFilter::class,
			new PropertyFilter( $this->templateEngine, $this->treeBuilder,  [] )
		);
	}

	public function testCreate_NoFilter() {

		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->will( $this->returnValue( '' ) );

		$params = [
			'min_item' => 1
		];

		$instance = new PropertyFilter(
			$this->templateEngine,
			$this->treeBuilder,
			$params
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$filters = [];

		$this->assertInternalType(
			'string',
			$instance->create( $this->urlArgs, $filters )
		);
	}

	public function testCreate_OneFilter() {

		$this->templateEngine->expects( $this->any() )
			->method( 'publish' )
			->will( $this->returnValue( '' ) );

		$params = [
			'min_item' => 1,
			'hierarchy_tree' => false
		];

		$instance = new PropertyFilter(
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

		$this->assertInternalType(
			'string',
			$instance->create( $this->urlArgs, $filters )
		);
	}

}

