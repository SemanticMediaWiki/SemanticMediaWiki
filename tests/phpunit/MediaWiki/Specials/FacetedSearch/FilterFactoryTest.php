<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\FilterFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\FilterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FilterFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $templateEngine;
	private $treeBuilder;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();

		$this->treeBuilder = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\TreeBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FilterFactory::class,
			new FilterFactory( $this->templateEngine, $this->treeBuilder, $this->schemaFactory )
		);
	}

}
