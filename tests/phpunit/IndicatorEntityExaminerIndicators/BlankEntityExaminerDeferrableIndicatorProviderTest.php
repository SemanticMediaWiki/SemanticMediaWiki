<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\DIWikiPage;
use SMW\Indicator\EntityExaminerIndicators\BlankEntityExaminerDeferrableIndicatorProvider;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\BlankEntityExaminerDeferrableIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class BlankEntityExaminerDeferrableIndicatorProviderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
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

		$this->assertIsBool(

			$instance->isDeferredMode()
		);
	}

	public function testGetName() {
		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testGetIndicators() {
		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator() {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new BlankEntityExaminerDeferrableIndicatorProvider();

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

}
