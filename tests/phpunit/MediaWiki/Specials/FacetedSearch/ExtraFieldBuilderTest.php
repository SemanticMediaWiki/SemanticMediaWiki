<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\Tests\Utils\Mock\IteratorMockBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ExtraFieldBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $profile;
	private $templateEngine;

	protected function setUp() : void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateEngine = $this->getMockBuilder( '\SMW\Utils\TemplateEngine' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ExtraFieldBuilder::class,
			new ExtraFieldBuilder( $this->profile, $this->templateEngine )
		);
	}

}

