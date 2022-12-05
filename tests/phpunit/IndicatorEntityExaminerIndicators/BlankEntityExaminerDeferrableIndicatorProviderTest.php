<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\BlankEntityExaminerDeferrableIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\BlankEntityExaminerDeferrableIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class BlankEntityExaminerDeferrableIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			BlankEntityExaminerDeferrableIndicatorProvider::class,
			new BlankEntityExaminerDeferrableIndicatorProvider()
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			new BlankEntityExaminerDeferrableIndicatorProvider()
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider',
			new BlankEntityExaminerDeferrableIndicatorProvider()
		);
	}

	public function testIsDeferredMode() {

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'bool',
			$instance->isDeferredMode()
		);
	}

	public function testGetName() {

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testGetIndicators() {

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testGetModules() {

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

}
