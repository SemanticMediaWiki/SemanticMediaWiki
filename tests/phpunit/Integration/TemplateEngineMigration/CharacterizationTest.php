<?php

namespace SMW\Tests\Integration\TemplateEngineMigration;

use MediaWiki\Html\TemplateParser;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\Elastic\Indexer\Replication\ReplicationEntityExaminerDeferrableIndicatorProvider;
use SMW\EntityCache;
use SMW\Indicator\EntityExaminerIndicators\AssociatedRevisionMismatchEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder;
use SMW\Indicator\EntityExaminerIndicators\ConstraintErrorEntityExaminerIndicatorProvider;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\MessageLocalizer;
use SMW\MediaWiki\RevisionGuard;
use SMW\SetupCheck;
use SMW\SetupFile;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\ErrorLookup;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\TemplateEngine;

/**
 * Characterization (snapshot) tests that lock the rendered HTML of every
 * `SMW\Utils\TemplateEngine` consumer while the code still uses `TemplateEngine`.
 *
 * These tests are a temporary safety net for the migration from
 * `SMW\Utils\TemplateEngine` to `MediaWiki\Html\TemplateParser`. They are
 * intentionally placed in a dedicated directory and are expected to be
 * deleted once per-consumer behaviour is covered by their own tests.
 *
 * Because they cross many classes, `@coversNothing` is used deliberately.
 *
 * @coversNothing
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class CharacterizationTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// The consumers and the `TemplateEngine` read real `.ms` files from
		// `$GLOBALS['smwgDir'] . '/data/template'`, so the global must be set.
		if ( !isset( $GLOBALS['smwgDir'] ) ) {
			$this->markTestSkipped( '`$GLOBALS["smwgDir"]` is not set in this environment.' );
		}

		// The static template cache is shared across instances, clear it so
		// each test starts from a known state.
		( new TemplateEngine() )->clearTemplates();
	}

	protected function tearDown(): void {
		( new TemplateEngine() )->clearTemplates();
		parent::tearDown();
	}

	private function newEntityExaminerTemplateParser(): TemplateParser {
		return new TemplateParser( __DIR__ . '/../../../../templates/EntityExaminer' );
	}

	private function newMessageLocalizer(): MessageLocalizer {
		$messageLocalizer = $this->getMockBuilder( MessageLocalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$messageLocalizer->method( 'msg' )
			->willReturn( '__msg__' );

		return $messageLocalizer;
	}

	private function newOptions(): array {
		$subject = WikiPage::newFromText( 'Foo' );

		return [
			'subject' => $subject->getHash(),
			'highlighter_title' => 'highlighter-title',
			'placeholder_title' => 'placeholder-title',
			'options_raw' => '',
			'dir' => 'ltr',
			'uselang' => 'en'
		];
	}

	public function testCompositeIndicatorHtmlBuilder_EmptyProviders() {
		$instance = new CompositeIndicatorHtmlBuilder( $this->newEntityExaminerTemplateParser() );
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );

		$html = $instance->buildHTML( [], $this->newOptions() );

		$this->assertSame( self::COMPOSITE_EMPTY, $html );
	}

	public function testCompositeIndicatorHtmlBuilder_CompositeProvider() {
		$compositeIndicatorProvider = $this->getMockBuilder( CompositeIndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$compositeIndicatorProvider->method( 'getIndicators' )
			->willReturn(
				[
					'abc_123' => [
						'content' => '__content_123__',
						'title' => '__title_123__'
					]
				]
			);

		$instance = new CompositeIndicatorHtmlBuilder( $this->newEntityExaminerTemplateParser() );
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );

		$html = $instance->buildHTML( [ $compositeIndicatorProvider ], $this->newOptions() );

		$this->assertSame( self::COMPOSITE_COMPOSITE_PROVIDER, $html );
	}

	public function testCompositeIndicatorHtmlBuilder_NonCompositeProvider() {
		$typableSeverityIndicatorProvider = $this->getMockBuilder( TypableSeverityIndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$typableSeverityIndicatorProvider->method( 'isSeverityType' )
			->willReturnCallback( static function ( $type ) {
				return $type === TypableSeverityIndicatorProvider::SEVERITY_ERROR;
			} );

		$typableSeverityIndicatorProvider->method( 'getIndicators' )
			->willReturn(
				[
					'id' => 'test-indicator',
					'title' => '__indicator_title__',
					'content' => '__indicator_content__'
				]
			);

		$instance = new CompositeIndicatorHtmlBuilder( $this->newEntityExaminerTemplateParser() );
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );

		$html = $instance->buildHTML( [ $typableSeverityIndicatorProvider ], $this->newOptions() );

		$this->assertSame( self::COMPOSITE_NON_COMPOSITE_PROVIDER, $html );
	}

	public function testAssociatedRevisionMismatchEntityExaminerIndicatorProvider() {
		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->method( 'findAssociatedRev' )
			->willReturn( 99 );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$revisionGuard = $this->getMockBuilder( RevisionGuard::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard->method( 'getLatestRevID' )
			->willReturn( 1001 );

		$instance = new AssociatedRevisionMismatchEntityExaminerIndicatorProvider( $store, $this->newEntityExaminerTemplateParser() );
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );
		$instance->setRevisionGuard( $revisionGuard );
		$instance->setDeferredMode( true );

		$subject = WikiPage::newFromText( 'Foo' );
		$hasIndicator = $instance->hasIndicator( $subject, [ 'uselang' => 'en', 'dir' => 'ltr' ] );

		$this->assertTrue( $hasIndicator );
		$this->assertSame( self::ASSOCIATED_REVISION_MISMATCH, $instance->getIndicators()['content'] );
	}

	public function testConstraintErrorEntityExaminerIndicatorProvider() {
		$errorLookup = $this->getMockBuilder( ErrorLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$errorLookup->method( 'findErrorsByType' )
			->willReturn( [] );

		$errorLookup->method( 'buildArray' )
			->willReturn( [ 'first error', 'second error', 'third error' ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$store->method( 'service' )
			->with( 'ErrorLookup' )
			->willReturn( $errorLookup );

		$entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$entityCache->method( 'makeKey' )
			->willReturn( 'test-cache-key' );

		$entityCache->method( 'fetch' )
			->willReturn( false );

		$instance = new ConstraintErrorEntityExaminerIndicatorProvider( $store, $entityCache, $this->newEntityExaminerTemplateParser() );
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );

		$subject = WikiPage::newFromText( 'Foo' );
		$hasIndicator = $instance->hasIndicator( $subject, [ 'uselang' => 'en', 'dir' => 'ltr' ] );

		$this->assertTrue( $hasIndicator );
		$this->assertSame( self::CONSTRAINT_ERROR, $instance->getIndicators()['content'] );
	}

	public function testEntityExaminerDeferrableCompositeIndicatorProvider() {
		$childIndicatorProvider = new CharacterizationTestIndicatorProvider();

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			[ $childIndicatorProvider ],
			$this->newEntityExaminerTemplateParser()
		);
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );
		$instance->setDeferredMode( true );

		$subject = WikiPage::newFromText( 'Foo' );
		$hasIndicator = $instance->hasIndicator( $subject, [ 'uselang' => 'en' ] );

		$this->assertTrue( $hasIndicator );
		$this->assertSame(
			self::DEFERRABLE_COMPOSITE,
			$instance->getIndicators()['test-deferrable-indicator']['content']
		);
	}

	public function testReplicationEntityExaminerDeferrableIndicatorProvider() {
		$connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'ping' )
			->willReturn( true );

		$connection->method( 'hasMaintenanceLock' )
			->willReturn( false );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMockForAbstractClass();

		$store->method( 'getConnection' )
			->willReturn( $connection );

		$entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->getMock();

		$entityCache->method( 'fetch' )
			->willReturn( false );

		$replicationCheck = $this->getMockBuilder( ReplicationCheck::class )
			->disableOriginalConstructor()
			->getMock();

		$replicationCheck->method( 'checkReplication' )
			->willReturn( '__replication_html__' );

		$replicationCheck->method( 'getSeverityType' )
			->willReturn( ReplicationCheck::SEVERITY_TYPE_ERROR );

		$instance = new ReplicationEntityExaminerDeferrableIndicatorProvider(
			$store,
			$entityCache,
			$replicationCheck,
			$this->newEntityExaminerTemplateParser()
		);
		$instance->setMessageLocalizer( $this->newMessageLocalizer() );
		$instance->setDeferredMode( true );
		$instance->canCheckReplication( true );

		$subject = WikiPage::newFromText( 'Foo' );
		$hasIndicator = $instance->hasIndicator( $subject, [ 'uselang' => 'en', 'dir' => 'ltr' ] );

		$this->assertTrue( $hasIndicator );
		$this->assertSame( self::REPLICATION_CHECK, $instance->getIndicators()['content'] );
	}

	public function testSetupCheck_HtmlOutput() {
		$setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$setupFile->method( 'getMaintenanceMode' )
			->willReturn( [] );

		$instance = new SetupCheck(
			[
				'SMW_VERSION' => '4.0.0',
				'MW_VERSION' => '1.43.0',
				'smwgUpgradeKey' => 'test-upgrade-key'
			],
			$setupFile
		);

		$instance->disableHeader();
		$instance->setErrorType( SetupCheck::ERROR_SCHEMA_INVALID_KEY );

		$html = $instance->getError( false );

		$this->assertSame( self::SETUP_CHECK_HTML, $html );
	}

	public function testSetupCheck_CliOutput() {
		$setupFile = $this->getMockBuilder( SetupFile::class )
			->disableOriginalConstructor()
			->getMock();

		$setupFile->method( 'getMaintenanceMode' )
			->willReturn( [] );

		$instance = new SetupCheck(
			[
				'SMW_VERSION' => '4.0.0',
				'MW_VERSION' => '1.43.0',
				'smwgUpgradeKey' => 'test-upgrade-key'
			],
			$setupFile
		);

		$instance->disableHeader();
		$instance->setErrorType( SetupCheck::ERROR_SCHEMA_INVALID_KEY );

		$content = $instance->getError( true );

		$this->assertSame( self::SETUP_CHECK_CLI, $content );
	}

	private const COMPOSITE_EMPTY = "<div class=\"smw-entity-examiner smw-indicator-vertical-bar-loader\" data-subject=\"Foo#0##\" data-dir=\"ltr\" data-uselang=\"en\" title=\"__msg__\"></div>\n";

	private const COMPOSITE_COMPOSITE_PROVIDER = "<div class=\"smw-entity-examiner smw-indicator-vertical-bar-loader\" data-subject=\"Foo#0##\" data-dir=\"ltr\" data-uselang=\"en\" title=\"__msg__\"></div>\n";

	private const COMPOSITE_NON_COMPOSITE_PROVIDER = <<<'HTML'
<div class="smw-highlighter smw-icon-entity-examiner-panel-error" data-maxWidth="280" data-tooltipclass="square-border-transparent-arrow" data-deferred="no" data-subject="Foo#0##" data-dir="ltr" data-uselang="en" data-state="persistent" data-placement="auto" data-animation="fade" data-theme="accordion-popup plain" data-count='1' data-options='' data-title='__msg__' data-top='&lt;div style=&quot;text-align: justify;&quot;&gt;&lt;span style=&quot;font-size:12px;&quot;&gt;__msg__&lt;/span&gt;&lt;/div&gt;
' data-content='&lt;div class=&quot;smw-tabset smw-issue-panel&quot;&gt;&lt;input type=&quot;radio&quot; name=&quot;tabset&quot; id=&quot;itabtest-indicator&quot; aria-controls=&quot;&quot; checked&gt;
&lt;label for=&quot;itabtest-indicator&quot; class=&quot;smw-indicator-severity-error&quot;&gt;&lt;span&gt;__indicator_title__&lt;/span&gt;&lt;/label&gt;
&lt;div class=&quot;tab-panels&quot;&gt;&lt;section id=&quot;itabtest-indicator&quot; class=&quot;tab-panel&quot;&gt;__indicator_content__&lt;/section&gt;
&lt;/div&gt;&lt;/div&gt;
' data-bottom=''></div>

HTML;

	private const ASSOCIATED_REVISION_MISMATCH = '<div style="padding-top:10px;text-align: justify;">__msg__</div>
<div class="smw-indicator-compare-list"><p></p><div class="smw-indicator-compare-list-row"><span>MediaWiki:</span><span>1001</span></div><div class="smw-indicator-compare-list-row"><span>Semantic MediaWiki:</span><span>99</span></div></div>
<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 280px;"></div>
<div style="text-align: justify;margin-bottom:10px;"><span style="font-size:12px;">__msg__</span></div>
';

	private const CONSTRAINT_ERROR = '<div style="padding-top:10px;"><ul><li></li><li></li><li></li></ul></div><div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 280px;"></div>
<div style="text-align: justify;"><span style="font-size:12px;">__msg__</span></div>
<div style="position: sticky; bottom: 0px;text-align: justify;background: #fff; padding-bottom: 20px;"><div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 280px;"></div><div style="padding-top: 2px;padding-bottom:2px;"><span class="smw-issue-label" style="background-color:#00BCD4;color:#ffffff;">constraint</span></div>
</div>
';

	private const DEFERRABLE_COMPOSITE = '<section id="test-deferrable-indicator" class="tab-panel">__deferred_content__</section>
';

	private const REPLICATION_CHECK = '__replication_html__<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 280px;"></div><div style="padding-top: 2px;padding-bottom:2px;"><span class="smw-issue-label" style="background-color:#cc317c;color:#ffffff;">elastic</span></div>
';

	private const SETUP_CHECK_HTML = <<<'HTML'
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
	<meta http-equiv="refresh" content="30" charset="UTF-8" />
	<title>Semantic MediaWiki</title>
	<style media='screen'>body {color:#000;background-color:#fff;font-family:sans-serif;padding:0em;}img, h1, h2, ul{text-align:left;margin:0.1em 0 0.3em;margin-left:10px;}p, h2, h4 {text-align:left;margin:0.5em 0 1em;margin-left:10px;margin-right:10px;}.title {}.nav {height:60px;background-color:#f8f9fa;padding-bottom:2px;margin-top:-8px;margin-left:-8px;margin-right:-8px;box-shadow:0 .125rem .25rem rgba(0,0,0,.075)!important;border-bottom:1px solid #ddd!important;}.nav-info {display:inline-block;font-weight:400;color:#fff;text-align:center;vertical-align:middle;font-size:1rem;line-height:1.5;border-radius:.15rem;float:right;padding:5px 30px;margin-top:17px;margin-right:15px;}.sticky {position:fixed;top:0;width:100%;}.content {margin-top:80px;line-height:1.4em;font-family:"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";}h1 {font-size:140%;}h2 {font-size:110%;}h3, h4 {font-size:100%;margin-left:10px;}code {color:black;background-color:#f9f9f9;border:1px solid #ddd;border-radius:2px;padding:1px 4px;}p + h4 {margin-top:20px;}.errorbox {color:#d33;border-color:#fac5c5;background-color:#fae3e3;border:0px solid;word-break:normal;padding:0.5em 0.5em;display:inline-block;zoom:1;margin-left:10px;margin-right:10px;}.errorbox + .errorbox {margin-top:20px;}pre {margin:0px;white-space:pre-wrap; /* css-3 */white-space:-moz-pre-wrap;/* Mozilla, since 1999 */white-space:-pre-wrap;/* Opera 4-6 */white-space:-o-pre-wrap;/* Opera 7 */word-wrap:break-word; /* Internet Explorer 5.5+ */}.progress-bar-animated {animation:progress-bar-stripes 2s linear infinite;}.progress-bar-striped {background-image:linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent);background-size:1rem 1rem;}.progress-bar-section {margin-right:20px;margin-bottom:10px;margin-top:10px;white-space:nowrap;padding:0 0 8px 0;}.progress-bar {background-color:#eee;transition:width .6s ease;justify-content:center;display:flex;white-space:nowrap;}.section {margin-right:8px;border-bottom:1px solid #dee2e6!important;padding-bottom:10px;}.section:first-of-type {margin-top:30px;}@keyframes progress-bar-stripes {from { background-position:28px 0; } to { background-position:0 0; }}</style>
</head>
<body>
<div class="nav sticky" style=" border-bottom: 4px solid #dd3d31;">
<span class="nav-info" style="background-color:#dd3d31">Error</span>
<h1 style="color:#222;margin-left:18px;padding-top:20px;"><img style='margin-top: 2px;margin-right:10px;margin-left:2px;float:left;' src='/extensions/SemanticMediaWiki/res/smw/assets/logo_small.svg' height='25' width='30'>Semantic MediaWiki</h1></div>
<div class="content"><!--paragraph--><p><a href='https://www.semantic-mediawiki.org/'>Semantic MediaWiki</a> was installed and enabled but is missing an appropriate <a href='https://www.semantic-mediawiki.org/wiki/Help:Upgrade_key'>upgrade key</a>.</p><!--version--><h4>Version</h4><p><div style="margin-left:10px;display: flex; width: 90%;"><div style="flex-basis:30%;">Semantic MediaWiki:</div><div>4.0.0&nbsp;(test-upgrade-key)</div></div><!-- ROW --><div style="margin-left:10px;display: flex; width: 90%;"><div style="flex-basis:30%;">MediaWiki:</div><div>1.43.0</div></div><!-- ROW --><div style="margin-top:10px;margin-left:10px;display: flex; width: 90%;"><div style="flex-basis:30%;">Code:</div><div><a href="https://www.semantic-mediawiki.org/wiki/Help:Setup_check/ERROR_SCHEMA_INVALID_KEY">ERROR_SCHEMA_INVALID_KEY</a></div></div></p>
<!--section--><h3 class="section"><span class="title">Why do I see this page?</span></h3><!--paragraph--><p>Semantic MediaWiki's internal database structure has changed and requires some adjustments to be fully functional. There can be several reasons including:<ul><li>Changes to the list of fixed properties and may require additional table(s)</li><li>Changes to the overall table structure or indices requirements</li><li>Changes to the selected storage or query engine</li><li>Changes to the required <a href='https://www.semantic-mediawiki.org/wiki/Entity_collation'>entity collation</a></li></ul></p><!--section--><h3 class="section"><span class="title">How can I fix this error?</span></h3><!--paragraph--><p>An administrator (or any person with administrator rights) has to run either MediaWiki's <a href='https://www.mediawiki.org/wiki/Manual:Update.php'>update.php</a> or Semantic MediaWiki's <a href='https://www.semantic-mediawiki.org/wiki/Help:SetupStore.php'>setupStore.php</a> maintenance script.</p><!--paragraph--><p>You may also consult the following pages for further assistance:<ul><li><a href='https://www.semantic-mediawiki.org/wiki/Help:Installation'>Installation</a> instructions</li><li><a href='https://www.semantic-mediawiki.org/wiki/Help:Installation/Troubleshooting'>Troubleshooting</a> help page</li></ul></p></div>
</body>
</html>
HTML;

	private const SETUP_CHECK_CLI = <<<'HTML'

Semantic MediaWiki

Semantic MediaWiki was installed and enabled but is missing an
appropriate upgrade key.

Version

Semantic MediaWiki:4.0.0 (test-upgrade-key)
MediaWiki:1.43.0
Code:ERROR_SCHEMA_INVALID_KEY


Why do I see this page?

Semantic MediaWiki's internal database structure has changed and requires
some adjustments to be fully functional. There can be several reasons
including:Changes to the list of fixed properties and may require
additional table(s)Changes to the overall table structure or indices
requirementsChanges to the selected storage or query engineChanges to the
required entity collation

How can I fix this error?

An administrator (or any person with administrator rights) has to run
either MediaWiki's update.php or Semantic MediaWiki's setupStore.php
maintenance script.

You may also consult the following pages for further
assistance:Installation instructionsTroubleshooting help page
HTML;

}

/**
 * Minimal deterministic indicator provider used to drive
 * `EntityExaminerDeferrableCompositeIndicatorProvider` in deferred mode.
 * It implements both interfaces the composite inspects.
 */
class CharacterizationTestIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider {

	public function setDeferredMode( bool $deferredMode ) {
	}

	public function isDeferredMode(): bool {
		return true;
	}

	public function isSeverityType( string $severityType ): bool {
		return $severityType === TypableSeverityIndicatorProvider::SEVERITY_WARNING;
	}

	public function getName(): string {
		return 'test-deferrable-indicator';
	}

	public function hasIndicator( WikiPage $subject, array $options ): bool {
		return true;
	}

	public function getIndicators(): array {
		return [ 'content' => '__deferred_content__' ];
	}

	public function getModules(): array {
		return [];
	}

	public function getInlineStyle(): string {
		return '';
	}

}
