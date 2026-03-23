<?php

namespace SMW\Tests\Unit\MediaWiki\Specials\FacetedSearch;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;

/**
 * @covers \SMW\MediaWiki\Specials\FacetedSearch\ExtraFieldBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ExtraFieldBuilderTest extends TestCase {

	private $profile;
	private $templateParser;

	protected function setUp(): void {
		parent::setUp();

		$this->profile = $this->getMockBuilder( Profile::class )
			->disableOriginalConstructor()
			->getMock();

		$this->templateParser = $this->getMockBuilder( TemplateParser::class )
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
