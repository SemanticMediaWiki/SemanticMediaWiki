<?php

namespace SMW\Tests\MediaWiki\Specials\FacetedSearch;

use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExtraFieldBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $profile;
	private $templateParser;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( '\SMW\MediaWiki\Specials\FacetedSearch\Profile' )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( '\MediaWiki\Html\TemplateParser' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExtraFieldBuilder::class,
			new ExtraFieldBuilder( $this->profile, $this->templateParser )
		);
	}

}
