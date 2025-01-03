<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder;
use SMW\Localizer\MessageLocalizer;
use SMW\Utils\TemplateEngine;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeIndicatorHtmlBuilderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $templateEngine;

	private MessageLocalizer $messageLocalizer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->templateEngine = new TemplateEngine();

		$this->messageLocalizer = $this->getMockBuilder( '\SMW\Localizer\MessageLocalizer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CompositeIndicatorHtmlBuilder::class,
			new CompositeIndicatorHtmlBuilder( $this->templateEngine )
		);
	}

	public function testBuildHTML_Empty() {
		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( '__foo__' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$options = [
			'subject' => $subject->getHash(),
			'highlighter_title' => '',
			'placeholder_title' => '',
			'options_raw' => '',
			'dir' => '',
			'uselang' => ''
		];

		$indicatorProviders = [];

		$instance = new CompositeIndicatorHtmlBuilder(
			$this->templateEngine
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->buildHTML( $indicatorProviders, $options );

		$this->assertContains(
			'<div class="smw-entity-examiner smw-indicator-vertical-bar-loader" ' .
			'data-subject="Foo#0##" data-dir="" data-uselang="" ' .
			'title="__foo__"></div>',
			$html
		);
	}

	public function testBuildHTML_TypedIndicator_SEVERITY_ERROR() {
		$typableSeverityIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$typableSeverityIndicatorProvider->expects( $this->any() )
			->method( 'isSeverityType' )
			->with( $typableSeverityIndicatorProvider::SEVERITY_ERROR )
			->willReturn( true );

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( '__foo__' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$options = [
			'subject' => $subject->getHash(),
			'highlighter_title' => '',
			'placeholder_title' => '',
			'options_raw' => '',
			'dir' => '',
			'uselang' => ''
		];

		$indicatorProviders = [
			$typableSeverityIndicatorProvider
		];

		$instance = new CompositeIndicatorHtmlBuilder(
			$this->templateEngine
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->buildHTML( $indicatorProviders, $options );

		$this->assertContains(
			'smw-highlighter smw-icon-entity-examiner-panel-error',
			$html
		);
	}

	public function testBuildHTML_TypedIndicator_SEVERITY_WARNING() {
		$typableSeverityIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$typableSeverityIndicatorProvider->expects( $this->any() )
			->method( 'isSeverityType' )
			->withConsecutive(
				[ $this->equalTo( $typableSeverityIndicatorProvider::SEVERITY_ERROR ) ],
				[ $this->equalTo( $typableSeverityIndicatorProvider::SEVERITY_WARNING ) ] )
			->willReturnOnConsecutiveCalls( false, true );

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( '__foo__' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$options = [
			'subject' => $subject->getHash(),
			'highlighter_title' => '',
			'placeholder_title' => '',
			'options_raw' => '',
			'dir' => '',
			'uselang' => ''
		];

		$indicatorProviders = [
			$typableSeverityIndicatorProvider
		];

		$instance = new CompositeIndicatorHtmlBuilder(
			$this->templateEngine
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->buildHTML( $indicatorProviders, $options );

		$this->assertContains(
			'smw-highlighter smw-icon-entity-examiner-panel-warning',
			$html
		);
	}

	public function testBuildHTML_Deferrable() {
		$deferrableIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( '__foo__' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$options = [
			'subject' => $subject->getHash(),
			'highlighter_title' => '',
			'placeholder_title' => '',
			'options_raw' => '',
			'dir' => '',
			'uselang' => ''
		];

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new CompositeIndicatorHtmlBuilder(
			$this->templateEngine
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->buildHTML( $indicatorProviders, $options );

		$this->assertContains(
			'data-deferred="yes"',
			$html
		);
	}

	public function testBuildHTML_Composite() {
		$composite = [
			'abc_123' => [ 'content' => '__content_123', 'title' => '_title_123' ]
		];

		$compositeIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$compositeIndicatorProvider->expects( $this->any() )
			->method( 'getIndicators' )
			->willReturn( $composite );

		$this->messageLocalizer->expects( $this->any() )
			->method( 'msg' )
			->willReturn( '__foo__' );

		$subject = DIWikiPage::newFromText( 'Foo' );

		$options = [
			'subject' => $subject->getHash(),
			'highlighter_title' => '',
			'placeholder_title' => '',
			'options_raw' => '',
			'dir' => '',
			'uselang' => ''
		];

		$indicatorProviders = [
			$compositeIndicatorProvider
		];

		$instance = new CompositeIndicatorHtmlBuilder(
			$this->templateEngine
		);

		$instance->setMessageLocalizer(
			$this->messageLocalizer
		);

		$html = $instance->buildHTML( $indicatorProviders, $options );

		$this->assertContains(
			'<div class="smw-entity-examiner smw-indicator-vertical-bar-loader" ' .
			'data-subject="Foo#0##" data-dir="" data-uselang="" ' .
			'title="__foo__"></div>',
			$html
		);
	}

}
