<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch\Filters;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\Filters\PropertyFilter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PropertyFilterTest extends TestCase {

	private $templateParser;
	private $treeBuilder;
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

		$this->treeBuilder = $this->getMockBuilder( TreeBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->urlArgs = $this->getMockBuilder( UrlArgs::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyFilter::class,
			new PropertyFilter( $this->templateParser, $this->treeBuilder, [] )
		);
	}

	public function testCreate_NoFilter() {
		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1
		];

		$instance = new PropertyFilter(
			$this->templateParser,
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
		$this->templateParser->expects( $this->any() )
			->method( 'processTemplate' )
			->willReturn( '' );

		$params = [
			'min_item' => 1,
			'hierarchy_tree' => false
		];

		$instance = new PropertyFilter(
			$this->templateParser,
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
