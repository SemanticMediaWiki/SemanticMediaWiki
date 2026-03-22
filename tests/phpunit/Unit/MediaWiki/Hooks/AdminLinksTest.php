<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use ALSection;
use ALTree;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\AdminLinks;

/**
 * @covers \SMW\MediaWiki\Hooks\AdminLinks
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AdminLinksTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AdminLinks::class,
			new AdminLinks()
		);
	}

	public function testProcess() {
		$adminLinksTree = $this->getMockBuilder( ALTree::class )
			->disableOriginalConstructor()
			->getMock();

		$browseSearchSection = $this->getMockBuilder( ALSection::class )
			->disableOriginalConstructor()
			->getMock();

		// Mock the getSection method to return our browse section
		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'getSection' )
			->willReturn( $browseSearchSection );

		// Track all sections added
		$sectionsAdded = [];
		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'addSection' )
			->willReturnCallback( function( $section, $name ) use ( &$sectionsAdded ) {
				$sectionsAdded[] = [ 'section' => $section, 'name' => $name ];
			} );

		$instance = new AdminLinks();
		$result = $instance->process( $adminLinksTree );

		// Verify the process method returns true
		$this->assertTrue( $result );

		// Verify sections were added
		$this->assertGreaterThan( 0, count( $sectionsAdded ) );
	}

	public function testProcessAddsDataStructureSection() {
		$adminLinksTree = $this->getMockBuilder( ALTree::class )
			->disableOriginalConstructor()
			->getMock();

		$browseSearchSection = $this->getMockBuilder( ALSection::class )
			->disableOriginalConstructor()
			->getMock();

		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'getSection' )
			->willReturn( $browseSearchSection );

		$sectionsAdded = [];
		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'addSection' )
			->willReturnCallback( function( $section, $name ) use ( &$sectionsAdded ) {
				$sectionsAdded[] = [ 'section' => $section, 'name' => $name ];
			} );

		$instance = new AdminLinks();
		$instance->process( $adminLinksTree );

		// Verify that at least one section was added (data structure section)
		$this->assertNotEmpty( $sectionsAdded );

		// Verify sections contain ALSection instances
		foreach ( $sectionsAdded as $item ) {
			$this->assertInstanceOf( ALSection::class, $item['section'] );
		}
	}

	public function testProcessHandlesMessagesCorrectly() {
		$adminLinksTree = $this->getMockBuilder( ALTree::class )
			->disableOriginalConstructor()
			->getMock();

		$browseSearchSection = $this->getMockBuilder( ALSection::class )
			->disableOriginalConstructor()
			->getMock();

		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'getSection' )
			->willReturn( $browseSearchSection );

		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'addSection' );

		$instance = new AdminLinks();

		// This should not throw any exceptions even with mocked wfMessage
		$result = $instance->process( $adminLinksTree );

		$this->assertTrue( $result );
	}

	public function testProcessReturnType() {
		$adminLinksTree = $this->getMockBuilder( ALTree::class )
			->disableOriginalConstructor()
			->getMock();

		$browseSearchSection = $this->getMockBuilder( ALSection::class )
			->disableOriginalConstructor()
			->getMock();

		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'getSection' )
			->willReturn( $browseSearchSection );

		$adminLinksTree->expects( $this->atLeastOnce() )
			->method( 'addSection' );

		$instance = new AdminLinks();
		$result = $instance->process( $adminLinksTree );

		// Verify that the return type is boolean
		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}

}
