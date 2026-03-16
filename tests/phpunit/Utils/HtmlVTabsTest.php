<?php

namespace SMW\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SMW\Utils\HtmlVTabs;

/**
 * @covers \SMW\Utils\HtmlVTabs
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlVTabsTest extends TestCase {

	protected function setUp(): void {
		HtmlVTabs::init();
	}

	public function testGetModules() {
		$this->assertIsArray(

			HtmlVTabs::getModules()
		);
	}

	public function testGetModuleStyles() {
		$this->assertIsArray(

			HtmlVTabs::getModuleStyles()
		);
	}

	public function testNav() {
		$this->assertStringContainsString(
			'<div class="smw-vtab-nav nav-right" data-foo="bar">FooHtml</div>',
			HtmlVTabs::nav( 'FooHtml', [ 'data-foo' => 'bar' ] )
		);
	}

	public function testActiveLink() {
		$this->assertStringContainsString(
			'<button id="vtab-item-tab-foo" class="smw-vtab-link nav-right active" data-baz="foobar" data-id="tab-foo" type="submit"><a href="#tab-foo">barLabel</a></button>',
			HtmlVTabs::navLink( 'foo', 'barLabel', HtmlVTabs::IS_ACTIVE, [ 'data-baz' => 'foobar' ] )
		);
	}

	public function testHiddenLink() {
		$this->assertEmpty(
			HtmlVTabs::navLink( 'foo', 'bar', HtmlVTabs::IS_HIDDEN, [ 'data-baz' => 'foobar' ] )
		);
	}

	public function testFindActiveLink() {
		$this->assertStringContainsString(
			'<button id="vtab-item-tab-foo" class="smw-vtab-link nav-right active" data-baz="foobar" data-id="tab-foo" type="submit"><a href="#tab-foo">barLabel</a></button>',
			HtmlVTabs::navLink( 'foo', 'barLabel', [ HtmlVTabs::FIND_ACTIVE_LINK => 'foo' ], [ 'data-baz' => 'foobar' ] )
		);

		$this->assertStringContainsString(
			'<button id="vtab-item-tab-foo" class="smw-vtab-link nav-right" data-baz="foobar" data-id="tab-foo" type="submit"><a href="#tab-foo">barLabel</a></button>',
			HtmlVTabs::navLink( 'foo', 'barLabel', [ HtmlVTabs::FIND_ACTIVE_LINK => 'bar' ], [ 'data-baz' => 'foobar' ] )
		);
	}

	public function testLinkRight() {
		$this->assertStringContainsString(
			'<button id="vtab-item-tab-foo" class="smw-vtab-link nav-right" data-id="tab-foo" type="submit"><a href="#tab-foo">bar</a></button>',
			HtmlVTabs::navLink( 'foo', 'bar' )
		);
	}

	public function testLinkLeft() {
		HtmlVTabs::setDirection( 'left' );

		$this->assertStringContainsString(
			'<button id="vtab-item-tab-foo" class="smw-vtab-link nav-left" data-id="tab-foo" type="submit"><a href="#tab-foo">bar</a></button>',
			HtmlVTabs::navLink( 'foo', 'bar' )
		);
	}

	public function testContentWithActiveDisplay() {
		HtmlVTabs::navLink( 'foo', 'barLabel', HtmlVTabs::IS_ACTIVE );

		$this->assertStringContainsString(
			'<div id="tab-foo" class="smw-vtab-content" data-baz="foobar">bar</div>',
			HtmlVTabs::content( 'foo', 'bar', [ 'data-baz' => 'foobar' ] )
		);
	}

	public function testContentWithNoneDisplay() {
		$this->assertStringContainsString(
			'<div id="tab-foo2" class="smw-vtab-content" data-baz="foobar" style="display:none;">bar</div>',
			HtmlVTabs::content( 'foo2', 'bar', [ 'data-baz' => 'foobar' ] )
		);
	}

}
