<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\IndicatorRegistry;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\IndicatorRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class IndicatorRegistryTest extends \PHPUnit\Framework\TestCase {

	private $indicatorProvider;
	private $permissionExaminer;

	protected function setUp(): void {
		$this->indicatorProvider = $this->getMockBuilder( '\SMW\Indicator\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
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
		$title = $this->getMockBuilder( '\Title' )
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
		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->once() )
			->method( 'setIndicators' );

		$instance = new IndicatorRegistry();
		$instance->attachIndicators( $outputPage );
	}

	public function testNoPermissionOnIndicatorProvider() {
		$title = $this->getMockBuilder( '\Title' )
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
		$title = $this->getMockBuilder( '\Title' )
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
		return new class() implements \SMW\Indicator\IndicatorProvider, \SMW\MediaWiki\Permission\PermissionAware {

			public function getName(): string {
				return '';
			}

			public function getInlineStyle() {
				return '';
			}

			public function hasIndicator( \SMW\DIWikiPage $subject, array $options ) {
				return false;
			}

			public function getModules() {
				return [];
			}

			public function getIndicators() {
				return [];
			}

			public function hasPermission( \SMW\MediaWiki\Permission\PermissionExaminer $permissionExaminer ): bool {
				return $permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}

	private function newPermissionExaminerAwareIndicatorProvider() {
		return new class() implements \SMW\Indicator\IndicatorProvider, \SMW\MediaWiki\Permission\PermissionExaminerAware {

			public function getName(): string {
				return '';
			}

			public function getInlineStyle() {
				return '';
			}

			public function hasIndicator( \SMW\DIWikiPage $subject, array $options ) {
				return false;
			}

			public function getModules() {
				return [];
			}

			public function getIndicators() {
				return [];
			}

			public function setPermissionExaminer( \SMW\MediaWiki\Permission\PermissionExaminer $permissionExaminer ) {
				// Just used as an example to check that the setter is run
				$permissionExaminer->hasPermissionOf( 'Foo' );
			}
		};
	}
}
