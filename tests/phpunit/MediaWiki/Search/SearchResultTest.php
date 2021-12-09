<?php

namespace SMW\Tests\MediaWiki\Search;

use SMW\MediaWiki\Search\SearchResult;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Search\SearchResult
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SearchResultTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testGetSectionTitle_WithFragment() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new SearchResult( $title );

		$this->assertInstanceOf(
			'\Title',
			$instance->getSectionTitle()
		);
	}

	public function testGetSectionTitle_WithoutFragment() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->will( $this->returnValue( '' ) );

		$instance = new SearchResult( $title );

		$this->assertNull(
			$instance->getSectionTitle()
		);
	}

	public function testExcerpt() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->will( $this->returnValue( '' ) );

		$instance = new SearchResult( $title );
		$instance->setExcerpt( 'Foo ...' );

		$this->assertEquals(
			'Foo ...',
			$instance->getExcerpt()
		);
	}

	public function testGetTitleSnippet() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getFragment' )
			->will( $this->returnValue( '' ) );

		$instance = new SearchResult( $title );

		$this->assertEquals(
			'Foo',
			$instance->getTitleSnippet()
		);
	}

	public function testGetTextSnippet_HasHighlight() {

		$title = $this->getMockBuilder( '\Title' )
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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SearchResult( $title );

		$instance->setExcerpt( 'Foobar' );

		$this->assertInternalType(
			'string',
			$instance->getTextSnippet( [ 'Foo' ] )
		);
	}

}
