<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Indicator\IndicatorProvider;
use SMW\MediaWiki\IndicatorRegistry;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;

/**
 * @covers \SMW\MediaWiki\IndicatorRegistry
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class IndicatorRegistryTest extends TestCase {

	private $indicatorProvider;
	private $permissionExaminer;

	protected function setUp(): void {
		$this->indicatorProvider = $this->getMockBuilder( IndicatorProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			IndicatorRegistry::class,
			 new IndicatorRegistry()
		);
	}

	public function testAddIndicatorProvider() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->indicatorProvider->expects( $this->once() )
			->method( 'hasIndicator' )
			->willReturn( true );

		$this->indicatorProvider->expects( $this->once() )
			->method( 'getIndicators' )
			->willReturn( [] );

		$this->indicatorProvider->expects( $this->once() )
			->method( 'getModules' )
			->willReturn( [] );

		$instance = new IndicatorRegistry();
		$instance->addIndicatorProvider( $this->indicatorProvider );

		$instance->hasIndicator( $title, $this->permissionExaminer, [] );
	}

	public function testAttachIndicators() {
		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->once() )
			->method( 'setIndicators' );

		$instance = new IndicatorRegistry();
		$instance->attachIndicators( $outputPage );
	}

	public function testNoPermissionOnIndicatorProvider() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( false );

		$instance = new IndicatorRegistry();
		$instance->addIndicatorProvider( $this->newPermissionAwareIndicatorProvider() );

		$instance->hasIndicator( $title, $this->permissionExaminer, [] );
	}

	public function testPermissionAwareIndicatorProvider() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$this->permissionExaminer->expects( $this->once() )
			->method( 'hasPermissionOf' )
			->willReturn( false );

		$instance = new IndicatorRegistry();
		$instance->addIndicatorProvider( $this->newPermissionExaminerAwareIndicatorProvider() );

		$instance->hasIndicator( $title, $this->permissionExaminer, [] );
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

	private function newPermissionExaminerAwareIndicatorProvider() {
		return new class() implements IndicatorProvider, PermissionExaminerAware {

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

			public function setPermissionExaminer( PermissionExaminer $permissionExaminer ) {
				// Just used as an example to check that the setter is run
				$permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}
}
