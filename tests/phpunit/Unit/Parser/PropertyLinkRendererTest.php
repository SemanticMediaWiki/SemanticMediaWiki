<?php

namespace SMW\Tests\Unit\Parser;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use PHPUnit\Framework\TestCase;
use SMW\Parser\PropertyLinkRenderer;
use SMW\ParserData;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Parser\PropertyLinkRenderer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.2.0
 */
class PropertyLinkRendererTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testRendersLinkToPropertyPage() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foo]]' ),
			$this->render( [ 'Foo' ], '@@@', false )
		);
	}

	public function testUsesCustomCaptionAsLinkText() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foobar]]' ),
			$this->render( [ 'Foo' ], '@@@', 'Foobar' )
		);
	}

	public function testLinksToLastPropertyOfChain() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Baz|Baz]]' ),
			$this->render( [ 'Foo', 'Baz' ], '@@@', false )
		);
	}

	public function testHashCaptionSuppressesLink() {
		$this->assertSame(
			'<span class="smw-property nolink">Foo</span>',
			$this->render( [ 'Foo' ], '@@@', '#' )
		);
	}

	public function testLanguageAnnotatedValueUsesPropertyLabelAsCaption() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foo]]' ),
			$this->render( [ 'Foo' ], '@@@en', false )
		);
	}

	public function testLanguageAnnotatedValueKeepsCustomCaption() {
		$this->assertSame(
			$this->propertyLink( '[[:Property:Foo|Foobar]]' ),
			$this->render( [ 'Foo' ], '@@@en', 'Foobar' )
		);
	}

	public function testValidPropertyWithoutTooltipDoesNotVaryByUserLanguage() {
		$parserData = $this->newParserData();

		$renderer = new PropertyLinkRenderer( $parserData );
		$renderer->render( [ 'Foo' ], '@@@', false );

		$this->assertFalse(
			$parserData->variesByUserLanguage()
		);
	}

	public function testInvalidPropertyVariesByUserLanguage() {
		$parserData = $this->newParserData();

		$renderer = new PropertyLinkRenderer( $parserData );
		$renderer->render( [ 'Fo%o' ], '@@@', false );

		$this->assertTrue(
			$parserData->variesByUserLanguage()
		);
	}

	private function render( array $properties, string $value, string|false $caption ): string {
		$renderer = new PropertyLinkRenderer( $this->newParserData() );

		return $renderer->render( $properties, $value, $caption );
	}

	private function newParserData(): ParserData {
		return new ParserData(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __CLASS__, NS_MAIN ),
			new ParserOutput()
		);
	}

	private function propertyLink( string $link ): string {
		return $this->testEnvironment->replaceNamespaceWithLocalizedText(
			SMW_NS_PROPERTY,
			'<span class="smw-property">' . $link . '</span>'
		);
	}

}
