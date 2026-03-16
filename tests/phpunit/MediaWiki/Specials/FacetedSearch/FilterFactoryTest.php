<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\FilterFactory;
use SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder;
use SMW\Schema\SchemaFactory;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\FilterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FilterFactoryTest extends TestCase {

	private $templateParser;
	private $treeBuilder;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->treeBuilder = $this->getMockBuilder( TreeBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FilterFactory::class,
			new FilterFactory( $this->templateParser, $this->treeBuilder, $this->schemaFactory )
		);
	}

}
