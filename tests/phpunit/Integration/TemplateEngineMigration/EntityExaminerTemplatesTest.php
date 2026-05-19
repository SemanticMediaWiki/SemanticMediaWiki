<?php

namespace SMW\Tests\Integration\TemplateEngineMigration;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;

/**
 * Renders the EntityExaminer Mustache templates through MediaWiki core's
 * TemplateParser to verify their view-model contracts and escaping behaviour.
 *
 * @coversNothing
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 *
 * @author Semantic MediaWiki
 */
class EntityExaminerTemplatesTest extends TestCase {

	private TemplateParser $templateParser;

	protected function setUp(): void {
		parent::setUp();

		$this->templateParser = new TemplateParser(
			__DIR__ . '/../../../../templates/EntityExaminer'
		);
	}

	public function testComment(): void {
		$html = $this->templateParser->processTemplate( 'Comment', [
			'html-comment' => '<b>note</b>',
		] );

		$this->assertStringContainsString( '<b>note</b>', $html );
		$this->assertStringContainsString( 'font-size:12px;', $html );
	}

	public function testBottomComment(): void {
		$html = $this->templateParser->processTemplate( 'BottomComment', [
			'html-comment' => '<b>note</b>',
		] );

		$this->assertStringContainsString( '<b>note</b>', $html );
		$this->assertStringContainsString( 'margin-bottom:10px;', $html );
	}

	public function testBottomMarker(): void {
		$html = $this->templateParser->processTemplate( 'BottomMarker', [
			'margin' => 'left',
			'data-background-color' => '#f00',
			'color' => '#fff',
			'label' => '<issue>',
		] );

		$this->assertStringContainsString( 'margin-left: -10px;', $html );
		$this->assertStringContainsString( 'background-color:#f00;', $html );
		$this->assertStringContainsString( 'color:#fff;', $html );
		$this->assertStringContainsString( '&lt;issue&gt;', $html );
	}

	public function testBottomSticky(): void {
		$html = $this->templateParser->processTemplate( 'BottomSticky', [
			'html-content' => '<span>body</span>',
		] );

		$this->assertStringContainsString( '<span>body</span>', $html );
		$this->assertStringContainsString( 'position: sticky; bottom: 0px;', $html );
	}

	public function testLine(): void {
		$html = $this->templateParser->processTemplate( 'Line', [
			'margin' => 'right',
		] );

		$this->assertStringContainsString( 'margin-right: -10px;', $html );
		$this->assertStringContainsString( 'border-top: 1px solid #ebebeb;', $html );
	}

	public function testText(): void {
		$html = $this->templateParser->processTemplate( 'Text', [
			'html-text' => '<em>text</em>',
		] );

		$this->assertStringContainsString( '<em>text</em>', $html );
		$this->assertStringContainsString( 'text-align: justify;', $html );
	}

	public function testCompareList(): void {
		$html = $this->templateParser->processTemplate( 'CompareList', [
			'explain' => 'why <x>',
			'first_key' => 'Rev A',
			'first_value' => '101',
			'second_key' => 'Rev B',
			'second_value' => '102',
		] );

		$this->assertStringContainsString( 'smw-indicator-compare-list', $html );
		$this->assertStringContainsString( 'why &lt;x&gt;', $html );
		$this->assertStringContainsString( '<span>Rev A</span>', $html );
		$this->assertStringContainsString( '<span>101</span>', $html );
		$this->assertStringContainsString( '<span>Rev B</span>', $html );
		$this->assertStringContainsString( '<span>102</span>', $html );
	}

	public function testTab(): void {
		$html = $this->templateParser->processTemplate( 'Tab', [
			'data-tab-id' => 'tab-1',
			'html-content' => '<p>panel</p>',
		] );

		$this->assertStringContainsString( 'id="tab-1"', $html );
		$this->assertStringContainsString( 'class="tab-panel"', $html );
		$this->assertStringContainsString( '<p>panel</p>', $html );
	}

	public function testTabsetChecked(): void {
		$html = $this->templateParser->processTemplate( 'Tabset', [
			'data-tab-id' => 'tab-1',
			'is-checked' => true,
			'data-severity-class' => 'severity-error',
			'title' => 'Errors <n>',
		] );

		$this->assertStringContainsString( 'id="tab-1"', $html );
		$this->assertStringContainsString( 'checked', $html );
		$this->assertStringContainsString( 'class="severity-error"', $html );
		$this->assertStringContainsString( 'Errors &lt;n&gt;', $html );
	}

	public function testTabsetUnchecked(): void {
		$html = $this->templateParser->processTemplate( 'Tabset', [
			'data-tab-id' => 'tab-2',
			'is-checked' => false,
			'data-severity-class' => '',
			'title' => 'Warnings',
		] );

		$this->assertStringNotContainsString( 'checked', $html );
	}

	public function testTabpanelPullsPartialsFromArrays(): void {
		$html = $this->templateParser->processTemplate( 'Tabpanel', [
			'array-tabset' => [
				[
					'data-tab-id' => 'tab-1',
					'is-checked' => true,
					'data-severity-class' => 'severity-error',
					'title' => 'Errors',
				],
				[
					'data-tab-id' => 'tab-2',
					'is-checked' => false,
					'data-severity-class' => '',
					'title' => 'Warnings',
				],
			],
			'array-panels' => [
				[
					'data-tab-id' => 'tab-1',
					'html-content' => '<p>errors</p>',
				],
				[
					'data-tab-id' => 'tab-2',
					'html-content' => '<p>warnings</p>',
				],
			],
		] );

		$this->assertStringContainsString( 'smw-tabset smw-issue-panel', $html );
		$this->assertStringContainsString( 'class="tab-panels"', $html );

		// Tabset partial entries.
		$this->assertStringContainsString( 'id="tab-1"', $html );
		$this->assertStringContainsString( 'id="tab-2"', $html );
		$this->assertStringContainsString( '<span>Errors</span>', $html );
		$this->assertStringContainsString( '<span>Warnings</span>', $html );

		// Tab partial entries.
		$this->assertStringContainsString( '<p>errors</p>', $html );
		$this->assertStringContainsString( '<p>warnings</p>', $html );
	}

	public function testCompositeHighlighterEscapesAttributePayloads(): void {
		$html = $this->templateParser->processTemplate( 'CompositeHighlighter', [
			'severity' => 'error',
			'has_deferred' => 'true',
			'subject' => 'Foo#0##',
			'dir' => 'ltr',
			'uselang' => 'en',
			'count' => '3',
			'data-options' => '{"a":1}',
			'data-title' => 'Title <t>',
			'data-top' => '<div>top</div>',
			'data-content' => '<div>content</div>',
			'data-bottom' => '<div>bottom</div>',
		] );

		$this->assertStringContainsString( 'smw-icon-entity-examiner-panel-error', $html );
		$this->assertStringContainsString( 'data-subject="Foo#0##"', $html );

		// Attribute payloads must be HTML-escaped for a single-quoted context.
		$this->assertStringContainsString( '&lt;div&gt;content&lt;/div&gt;', $html );
		$this->assertStringContainsString( '&lt;div&gt;top&lt;/div&gt;', $html );
		$this->assertStringContainsString( '&lt;div&gt;bottom&lt;/div&gt;', $html );
		$this->assertStringContainsString( 'Title &lt;t&gt;', $html );
		$this->assertStringNotContainsString( '<div>content</div>', $html );
	}

	public function testCompositePlaceholder(): void {
		$html = $this->templateParser->processTemplate( 'CompositePlaceholder', [
			'subject' => 'Foo#0##',
			'dir' => 'ltr',
			'uselang' => 'en',
			'title' => 'Loading',
		] );

		$this->assertStringContainsString( 'smw-entity-examiner', $html );
		$this->assertStringContainsString( 'data-subject="Foo#0##"', $html );
		$this->assertStringContainsString( 'title="Loading"', $html );
	}

	public function testConstraintTopLine(): void {
		$html = $this->templateParser->processTemplate( 'ConstraintTopLine', [
			'margin' => 'left',
		] );

		$this->assertStringContainsString( 'margin-left: -10px;', $html );
		$this->assertStringContainsString( 'margin-top: 5px;', $html );
	}

	public function testConstraintStickyTop(): void {
		$html = $this->templateParser->processTemplate( 'ConstraintStickyTop', [
			'html-content' => '<span>body</span>',
		] );

		$this->assertStringContainsString( '<span>body</span>', $html );
		$this->assertStringContainsString( 'position: sticky; top: 0px;', $html );
	}

	public function testElasticText(): void {
		$html = $this->templateParser->processTemplate( 'ElasticText', [
			'error_code' => 'E42',
			'html-text' => '<em>text</em>',
		] );

		$this->assertStringContainsString( 'data-error-code="E42"', $html );
		$this->assertStringContainsString( '<em>text</em>', $html );
	}

	public function testElasticCompareList(): void {
		$html = $this->templateParser->processTemplate( 'ElasticCompareList', [
			'explain' => 'why <x>',
			'es_key' => 'ES',
			'es_value' => '5',
			'backend_key' => 'DB',
			'backend_value' => '7',
		] );

		$this->assertStringContainsString( 'smw-indicator-compare-list', $html );
		$this->assertStringContainsString( 'why &lt;x&gt;', $html );
		$this->assertStringContainsString( '<span>ES</span>', $html );
		$this->assertStringContainsString( '<span>5</span>', $html );
		$this->assertStringContainsString( '<span>DB</span>', $html );
		$this->assertStringContainsString( '<span>7</span>', $html );
	}

}
