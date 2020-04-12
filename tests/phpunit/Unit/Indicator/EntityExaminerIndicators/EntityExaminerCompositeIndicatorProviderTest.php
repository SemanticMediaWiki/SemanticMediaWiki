<?php

namespace SMW\Tests\IndicatorEntityExaminerIndicators;

use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerCompositeIndicatorProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $compositeIndicatorHtmlBuilder;
	private $testEnvironment;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->compositeIndicatorHtmlBuilder = $this->getMockBuilder( '\SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$indicatorProviders = [];

		$this->assertInstanceOf(
			'\SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider',
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider',
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Permission\PermissionExaminerAware',
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);
	}

	public function testGetName() {

		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getName()
		);
	}

	public function testGetIndicators() {

		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getIndicators()
		);
	}

	public function testGetModules() {

		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'array',
			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {

		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'string',
			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_Empty() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_Option_ActionEdit() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertFalse(
			$instance->hasIndicator( $subject, [ 'action' => 'edit' ] )
		);
	}

	public function testHasIndicator_Option_Diff() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertFalse(
			$instance->hasIndicator( $subject, [ 'diff' => 42 ] )
		);
	}

	public function testHasIndicator_SomeContent() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->compositeIndicatorHtmlBuilder->expects( $this->once() )
			->method( 'buildHTML' )
			->will( $this->returnValue( '...' ) );

		$indicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testNoIndicatorOnFailedPermission() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->compositeIndicatorHtmlBuilder->expects( $this->never() )
			->method( 'buildHTML' );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->will( $this->returnValue( false ) );

		$indicatorProviders = [
			$this->newPermissionAwareIndicatorProvider()
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$instance->setPermissionExaminer(
			$this->permissionExaminer
		);

		$this->assertInternalType(
			'bool',
			$instance->hasIndicator( $subject, [] )
		);
	}

	private function newPermissionAwareIndicatorProvider() {
		return new class() implements \SMW\Indicator\IndicatorProvider, \SMW\MediaWiki\Permission\PermissionAware {

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
