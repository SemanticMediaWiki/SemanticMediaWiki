<?php

namespace SMW\Tests\Unit\IndicatorEntityExaminerIndicators;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Indicator\EntityExaminerIndicators\EntityExaminerDeferrableCompositeIndicatorProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerDeferrableCompositeIndicatorProviderTest extends TestCase {

	private $permissionExaminer;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$indicatorProviders = [];

		$this->assertInstanceOf(
			EntityExaminerDeferrableCompositeIndicatorProvider::class,
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			DeferrableIndicatorProvider::class,
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			CompositeIndicatorProvider::class,
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);

		$this->assertInstanceOf(
			PermissionExaminerAware::class,
			new EntityExaminerDeferrableCompositeIndicatorProvider( $indicatorProviders )
		);
	}

	public function testIsDeferredMode() {
		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsBool(

			$instance->isDeferredMode()
		);
	}

	public function testGetName() {
		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsString(

			$instance->getName()
		);
	}

	public function testGetIndicators() {
		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsArray(

			$instance->getIndicators()
		);
	}

	public function testGetModules() {
		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsArray(

			$instance->getModules()
		);
	}

	public function testGetInlineStyle() {
		$indicatorProviders = [];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsString(

			$instance->getInlineStyle()
		);
	}

	public function testHasIndicator() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( DeferrableIndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->willReturn( true );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->willReturn( [] );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testHasIndicator_DeferredMode() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$deferrableIndicatorProvider = $this->getMockBuilder( DeferrableIndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'setDeferredMode' );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'hasIndicator' )
			->willReturn( true );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getModules' )
			->willReturn( [] );

		$deferrableIndicatorProvider->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->willReturn( 'Foo' );

		$deferrableIndicatorProvider->expects( $this->once() )
			->method( 'getIndicators' )
			->willReturn( [ 'content' => '' ] );

		$indicatorProviders = [
			$deferrableIndicatorProvider
		];

		$instance = new EntityExaminerDeferrableCompositeIndicatorProvider(
			$indicatorProviders
		);

		$instance->setDeferredMode( true );

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	public function testNoIndicatorOnFailedPermission() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( false );

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

		$this->assertIsBool(

			$instance->hasIndicator( $subject, [] )
		);
	}

	private function newPermissionAwareIndicatorProvider() {
		return new class() implements DeferrableIndicatorProvider, PermissionAware {

			public function setDeferredMode( bool $deferredMode ) {
			}

			public function isDeferredMode(): bool {
				return true;
			}

			public function getName(): string {
				return '';
			}

			public function getInlineStyle() {
				return '';
			}

			public function hasIndicator( WikiPage $subject, array $options ) {
				return false;
			}

			public function getModules() {
				return [];
			}

			public function getIndicators() {
				return [];
			}

			public function hasPermission( PermissionExaminer $permissionExaminer ): bool {
				return $permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}

}
