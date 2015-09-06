<?php

namespace SMW\Tests;

use SMW\ControlledVocabularyImportContentFetcher;

/**
 * @covers \SMW\ControlledVocabularyImportContentFetcher
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ControlledVocabularyImportContentFetcherTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ControlledVocabularyImportContentFetcher',
			new ControlledVocabularyImportContentFetcher( $mediaWikiNsContentReader )
		);
	}

	public function testContainsForNonExistingImportNamespace() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( '' ) );

		$instance = new ControlledVocabularyImportContentFetcher( $mediaWikiNsContentReader );

		$this->assertFalse(
			$instance->contains( 'Foo' )
		);
	}

	public function testFetchForNonExistingImportNamespace() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ControlledVocabularyImportContentFetcher( $mediaWikiNsContentReader );

		$this->assertEmpty(
			$instance->fetchFor( 'Foo' )
		);
	}

	public function testForImportedNamespace() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$mediaWikiNsContentReader->expects( $this->once() )
			->method( 'read' )
			->will( $this->returnValue( 'Bar|Type:Page' ) );

		$instance = new ControlledVocabularyImportContentFetcher( $mediaWikiNsContentReader );

		$this->assertTrue(
			$instance->contains( 'Foo' )
		);

		$this->assertEquals(
			'Bar|Type:Page',
			$instance->fetchFor( 'Foo' )
		);
	}

	public function testForNotImportedNamespace() {

		$mediaWikiNsContentReader = $this->getMockBuilder( '\SMW\MediaWiki\MediaWikiNsContentReader' )
			->disableOriginalConstructor()
			->getMock();

		$mediaWikiNsContentReader->expects( $this->atLeastOnce() )
			->method( 'read' )
			->will( $this->returnValue( '' ) );

		$instance = new ControlledVocabularyImportContentFetcher( $mediaWikiNsContentReader );

		$this->assertFalse(
			$instance->contains( 'Foo' )
		);

		$this->assertEmpty(
			$instance->fetchFor( 'Foo' )
		);
	}

}
