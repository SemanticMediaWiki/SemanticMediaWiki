<?php

namespace SMW\Tests\Integration\TemplateEngineMigration;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;

/**
 * Renders the SetupCheck Mustache templates through MediaWiki core's
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
class SetupCheckTemplatesTest extends TestCase {

	private TemplateParser $templateParser;

	protected function setUp(): void {
		parent::setUp();

		$this->templateParser = new TemplateParser(
			__DIR__ . '/../../../../templates/SetupCheck'
		);
	}

	public function testSetupCheck(): void {
		$html = $this->templateParser->processTemplate( 'SetupCheck', [
			'refresh' => '60',
			'title' => 'Setup <check>',
			'borderColor' => '#d33',
			'indicator' => 'Error',
			'logo' => 'https://example.org/logo.png',
			'html-content' => '<div class="errorbox"><b>boom</b></div>',
		] );

		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
		$this->assertStringContainsString( 'content="60"', $html );
		$this->assertStringContainsString( 'border-bottom: 4px solid #d33;', $html );
		$this->assertStringContainsString( 'src=\'https://example.org/logo.png\'', $html );
		$this->assertStringContainsString( '@keyframes progress-bar-stripes', $html );

		// Escaped scalar.
		$this->assertStringContainsString( 'Setup &lt;check&gt;', $html );

		// Raw, pre-rendered body HTML survives unescaped.
		$this->assertStringContainsString( '<div class="errorbox"><b>boom</b></div>', $html );
	}

	public function testSection(): void {
		$html = $this->templateParser->processTemplate( 'Section', [
			'data-template' => 'section',
			'text' => 'Heading <x>',
		] );

		$this->assertStringContainsString( '<!--section-->', $html );
		$this->assertStringContainsString( '<h3 class="section">', $html );

		// Plain message string is escaped.
		$this->assertStringContainsString( 'Heading &lt;x&gt;', $html );
	}

	public function testParagraph(): void {
		$html = $this->templateParser->processTemplate( 'Paragraph', [
			'data-template' => 'paragraph',
			'html-text' => 'line one<br>line two',
		] );

		$this->assertStringContainsString( '<!--paragraph-->', $html );

		// Pre-rendered markup survives unescaped.
		$this->assertStringContainsString( '<p>line one<br>line two</p>', $html );
	}

	public function testErrorbox(): void {
		$html = $this->templateParser->processTemplate( 'Errorbox', [
			'data-template' => 'errorbox',
			'html-text' => '<b>trace</b>',
		] );

		$this->assertStringContainsString( '<!--errorbox-->', $html );
		$this->assertStringContainsString( '<div class="errorbox"><pre>', $html );

		// Raw stack trace / markup survives unescaped.
		$this->assertStringContainsString( '<b>trace</b>', $html );
	}

	public function testProgress(): void {
		$html = $this->templateParser->processTemplate( 'Progress', [
			'data-template' => 'progress',
			'label' => 'Building <index>',
			'value' => '42',
		] );

		$this->assertStringContainsString( '<!--progress-->', $html );
		$this->assertStringContainsString( 'width:42%;', $html );

		// Escaped scalar.
		$this->assertStringContainsString( 'Building &lt;index&gt;', $html );
	}

	public function testVersion(): void {
		$html = $this->templateParser->processTemplate( 'Version', [
			'data-template' => 'version',
			'version-title' => 'Versions',
			'smw-title' => 'Semantic MediaWiki',
			'smw-version' => '7.0.0',
			'smw-upgradekey' => 'abc123',
			'mw-title' => 'MediaWiki',
			'mw-version' => '1.43.0',
			'code-title' => 'Code',
			'code-type' => 'db-requirement',
		] );

		$this->assertStringContainsString( '<!--version-->', $html );
		$this->assertStringContainsString( '<h4>Versions</h4>', $html );
		$this->assertStringContainsString( '7.0.0&nbsp;(abc123)', $html );
		$this->assertStringContainsString( '1.43.0', $html );
		$this->assertStringContainsString(
			'https://www.semantic-mediawiki.org/wiki/Help:Setup_check/db-requirement',
			$html
		);

		// The <!-- ROW --> markers must survive verbatim for the CLI path.
		$this->assertSame( 2, substr_count( $html, '<!-- ROW -->' ) );
	}

	public function testDbRequirement(): void {
		$html = $this->templateParser->processTemplate( 'DbRequirement', [
			'data-template' => 'db-requirement',
			'version-title' => 'Database',
			'db-title' => 'Type',
			'db-type' => 'mysql',
			'db-minimum-title' => 'Minimum',
			'db-minimum-version' => '5.7',
			'db-current-title' => 'Current',
			'db-current-version' => '8.0',
		] );

		$this->assertStringContainsString( '<!--db-requirement-->', $html );
		$this->assertStringContainsString( '<h4>Database</h4>', $html );
		$this->assertStringContainsString( 'mysql', $html );
		$this->assertStringContainsString( '5.7', $html );
		$this->assertStringContainsString( '8.0', $html );

		// The <!-- ROW --> markers must survive verbatim for the CLI path.
		$this->assertSame( 2, substr_count( $html, '<!-- ROW -->' ) );
	}

	public function testScalarFieldsAreEscaped(): void {
		$html = $this->templateParser->processTemplate( 'Section', [
			'data-template' => '<marker>',
			'text' => '<b>plain</b>',
		] );

		// Escaped scalar fields must not emit raw markup.
		$this->assertStringContainsString( '&lt;b&gt;plain&lt;/b&gt;', $html );
		$this->assertStringContainsString( '<!--&lt;marker&gt;-->', $html );
		$this->assertStringNotContainsString( '<b>plain</b>', $html );
	}

}
