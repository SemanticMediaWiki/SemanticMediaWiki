<?php

namespace SMW\Tests\MediaWiki\Search;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Search\SearchResult;

/**
 * @covers \SMW\MediaWiki\Search\SearchResult
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResultTest extends TestCase {

	public function testGetSectionTitle_WithFragment() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->willReturn( 'Foo' );

		$instance = new SearchResult( $title );

		$this->assertInstanceOf(
			Title::class,
			$instance->getSectionTitle()
		);
	}

	public function testGetSectionTitle_WithoutFragment() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->willReturn( '' );

		$instance = new SearchResult( $title );

		$this->assertNull(
			$instance->getSectionTitle()
		);
	}

	public function testExcerpt() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->willReturn( '' );

		$instance = new SearchResult( $title );
		$instance->setExcerpt( 'Foo ...' );

		$this->assertEquals(
			'Foo ...',
			$instance->getExcerpt()
		);
	}

	public function testGetTitleSnippet() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->willReturn( '' );

		$instance = new SearchResult( $title );

		$this->assertEquals(
			'Foo',
			$instance->getTitleSnippet()
		);
	}

	public function testGetTextSnippet_HasHighlight() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SearchResult( $title );

		$instance->setExcerpt( '<em>Foo</em>bar', true );

		$this->assertEquals(
			"<span class='searchmatch'>Foo</span>bar",
			$instance->getTextSnippet( [ 'Foo' ] )
		);
	}

	public function testGetTextSnippet_NoHighlight() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SearchResult( $title );

		$instance->setExcerpt( 'Foobar' );

		$this->assertIsString(

			$instance->getTextSnippet( [ 'Foo' ] )
		);
	}

}
