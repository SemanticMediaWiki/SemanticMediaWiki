<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerDeferrableCompositeIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $permissionExaminer;
	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$indicatorProviders = [];

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Permission\PermissionExaminerAware',
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);
	}

	public function testIsDeferredMode() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->isDeferredMode()
		);
	}

	public function testGetName() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testGetIndicators() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testGetModules() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo' ) );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_DeferredMode() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Foo' ) );

		$deferrableIndicatorProvider->expects( $this->once() )
			->method( 'getIndicators' )
			->will( $this->returnValue( [ 'content' => '' ] ) );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$instance->setDeferredMode( true );

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testNoIndicatorOnFailedPermission() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->will( $this->returnValue( false ) );

		$indicatorProviders = [
			$this->newPermissionAwareIndicatorProvider()
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$instance->setPermissionExaminer(
			$this->permissionExaminer
		);

		$instance->setDeferredMode( true );

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	private function newPermissionAwareIndicatorProvider() {
		return new class() implements \SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider, \SMW\MediaWiki\Permission\PermissionAware {

			public function setDeferredMode( bool $deferredMode ) {
			}

			public function isDeferredMode() : bool {
				return true;
			}

			public function getName() : string {
				return '';
			}

			public function getInlineStyle() {
				return '';
			}

			public function hasIndicator( \SMW\DIWikiPage $subject, array $options) {
				return false;
			}

			public function getModules() {
				return [];
			}

			public function getIndicators() {
				return [];
			}

			public function hasPermission( \SMW\MediaWiki\Permission\PermissionExaminer $permissionExaminer ) : bool {
				return $permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}

}
