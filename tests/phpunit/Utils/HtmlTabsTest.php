<?php

namespace SMW\Tests\Utils;

use SMW\Tests\PHPUnitCompat;
use SMW\Utils\HtmlTabs;

/**
 * @covers \SMW\Utils\HtmlTabs
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlTabsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testHasContents() {
		$instance = new HtmlTabs();

		$this->assertIsBool(

			$instance->hasContents()
		);
	}

	public function testTab_Contents() {
		$instance = new HtmlTabs();
		$instance->setActiveTab( 'foo' );
		$instance->tab( 'foo', 'FOO' );
		$instance->content( 'foo', '< ... bar ... >' );

		$actual = $instance->buildHTML( [ 'class' => 'foo-bar' ] );
		// MW 1.39-1.40 produces self-closing tag, which is invalid HTML
		$actual = str_replace( '/>', '>', $actual );

		$this->assertContains(
			'<div class="smw-tabs foo-bar">' .
			'<input id="tab-foo" class="nav-tab" type="radio" name="tabs" checked="">' .
			'<label id="tab-label-foo" for="tab-foo" class="nav-label">FOO</label>' .
			'<section id="tab-content-foo">< ... bar ... ></section>' .
			'</div>',
			$actual
		);
	}

	public function testTab_Contents_Subtab() {
		if ( version_compare( MW_VERSION, '1.41', '>=' ) ) {
			$this->markTestSkipped( 'Check assertions for MW 1.41 and higher versions.' );
		}
		$instance = new HtmlTabs();
		$instance->setActiveTab( 'foo' );
		$instance->isSubTab();

		$instance->tab( 'foo', 'FOO' );
		$instance->content( 'foo', '< ... bar ... >' );

		$actual = $instance->buildHTML( [ 'class' => 'foo-bar' ] );
		// MW 1.39-1.40 produces self-closing tag, which is invalid HTML
		$actual = str_replace(
			htmlspecialchars( '/>' ),
			htmlspecialchars( '>' ),
			$actual
		);

		$this->assertContains(
			'<div class="smw-tabs smw-subtab foo-bar" ' .
			'data-subtab="&quot;&lt;input id=\&quot;tab-foo\&quot; ' .
			'class=\&quot;nav-tab\&quot; type=\&quot;radio\&quot; ' .
			'name=\&quot;tabs\&quot; checked=\&quot;\&quot;\&gt;&lt;label ' .
			'id=\&quot;tab-label-foo\&quot; for=\&quot;tab-foo\&quot; ' .
			'class=\&quot;nav-label\&quot;&gt;FOO&lt;\/label&gt;&quot;">' .
			'<div id="tab-content-foo" class="subtab-content">< ... bar ... ></div></div>',
			$actual
		);
	}

	public function testTab_Contents_AutoChecked() {
		$instance = new HtmlTabs();
		$instance->tab( 'foo', 'FOO' );
		$instance->content( 'foo', '< ... bar ... >' );

		$actual = $instance->buildHTML( [ 'class' => 'foo-bar' ] );
		// MW 1.39-1.40 produces self-closing tag, which is invalid HTML
		$actual = str_replace( '/>', '>', $actual );

		$this->assertContains(
			'<div class="smw-tabs foo-bar">' .
			'<input id="tab-foo" class="nav-tab" type="radio" name="tabs" checked="">' .
			'<label id="tab-label-foo" for="tab-foo" class="nav-label">FOO</label>' .
			'<section id="tab-content-foo">< ... bar ... ></section>' .
			'</div>',
			$actual
		);
	}

	public function testTab_Contents_Hidden() {
		$instance = new HtmlTabs();

		$instance->tab( 'foo', 'FOO', [ 'hide' => true ] );
		$instance->content( 'foo', '< ... bar ... >' );

		$this->assertContains(
			'<div class="smw-tabs foo-bar"></div>',
			$instance->buildHTML( [ 'class' => 'foo-bar' ] )
		);
	}

	public function testTab_WithExtraHtml() {
		$instance = new HtmlTabs();

		$instance->tab( 'foo', 'FOO' );
		$instance->content( 'foo', '< ... bar ... >' );
		$instance->html( '<span>Foobar</span>' );

		$this->assertContains(
			'<span>Foobar</span>',
			$instance->buildHTML()
		);
	}

	public function testIsRTL() {
		$instance = new HtmlTabs();
		$instance->isRTL( true );

		$instance->tab( 'foo', 'FOO' );
		$instance->content( 'foo', '< ... bar ... >' );

		$this->assertContains(
			'<div class="smw-tabs" dir="rtl">',
			$instance->buildHTML()
		);
	}

}
