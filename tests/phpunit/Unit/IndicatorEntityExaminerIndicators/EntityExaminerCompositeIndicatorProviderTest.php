<?php

namespace SMW\Tests\Unit\IndicatorEntityExaminerIndicators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Indicator\EntityExaminerIndicators\CompositeIndicatorHtmlBuilder;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider;
use SMW\Indicator\IndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\EntityExaminerCompositeIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerCompositeIndicatorProviderTest extends TestCase {

	private $compositeIndicatorHtmlBuilder;

	private PermissionExaminer $permissionExaminer;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->compositeIndicatorHtmlBuilder = $this->getMockBuilder( CompositeIndicatorHtmlBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$indicatorProviders = [];

		$this->assertInstanceOf(
			EntityExaminerCompositeIndicatorProvider::class,
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);

		$this->assertInstanceOf(
			CompositeIndicatorProvider::class,
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);

		$this->assertInstanceOf(
			PermissionExaminerAware::class,
			new EntityExaminerCompositeIndicatorProvider( $this->compositeIndicatorHtmlBuilder, $indicatorProviders )
		);
	}

	public function testGetName() {
		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testGetIndicators() {
		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$indicatorProviders = [];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator_Empty() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( IndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->willReturn( true );

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->willReturn( [] );

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_Option_ActionEdit() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( IndicatorProvider::class )
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
		$subject = WikiPage::newFromText( __METHOD__ );

		$indicatorProvider = $this->getMockBuilder( IndicatorProvider::class )
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
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->compositeIndicatorHtmlBuilder->expects( $this->once() )
			->method( 'buildHTML' )
			->willReturn( '...' );

		$indicatorProvider = $this->getMockBuilder( IndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->willReturn( true );

		$indicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->willReturn( [] );

		$indicatorProviders = [
			$indicatorProvider
		];

		$instance = new EntityExaminerCompositeIndicatorProvider(
			$this->compositeIndicatorHtmlBuilder,
			$indicatorProviders
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testNoIndicatorOnFailedPermission() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->compositeIndicatorHtmlBuilder->expects( $this->never() )
			->method( 'buildHTML' );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( false );

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

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	private function newPermissionAwareIndicatorProvider() {
		return new class() implements IndicatorProvider, PermissionAware {

			public function getName(): string {
				return '';
			}

			public function getInlineStyle() {
				return '';
			}

			public function hasIndicator( WikiPage $subject, array $options ): bool {
				return false;
			}

			public function getModules(): array {
				return [];
			}

			public function getIndicators(): array {
				return [];
			}

			public function hasPermission( PermissionExaminer $permissionExaminer ): bool {
				return $permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}

}
