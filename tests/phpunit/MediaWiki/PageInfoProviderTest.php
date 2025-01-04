<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\PageInfoProvider;
use SMW\Tests\Utils\Mock\MockTitle;

/**
 * @covers \SMW\MediaWiki\PageInfoProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PageInfoProviderTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$wikipage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageInfoProvider',
			new PageInfoProvider( $wikipage )
		);
	}

	public function testWikiPage_TYPE_MODIFICATION_DATE() {
		$instance = $this->constructPageInfoProviderInstance(
			[
				'wikiPage' => [ 'getTimestamp' => 1272508903 ],
				'revision' => [],
				'user'     => [],
			]
		);

		$this->assertEquals( 1272508903, $instance->getModificationDate() );
	}

	public function testWikiPage_TYPE_CREATION_DATE() {
		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getTimestamp' )
			->willReturn( 1272508903 );

		$title = MockTitle::buildMock( 'Lula' );

		$instance = $this->constructPageInfoProviderInstance(
			[
				'wikiPage' => [ 'getTitle' => $title ],
				'revision' => [],
				'user'     => [],
			]
		);

		$revisionLookup = $this->getMockBuilder( '\MediaWiki\Revision\RevisionLookup' )
			->disableOriginalConstructor()
			->getMock();

		$revisionLookup->expects( $this->any() )
			->method( 'getFirstRevision' )
			->willReturn( $revision );

		$instance->setRevisionLookup(
			$revisionLookup
		);

		$this->assertEquals( 1272508903, $instance->getCreationDate() );
	}

	/**
	 * @dataProvider parentIdProvider
	 */
	public function testWikiPage_TYPE_NEW_PAGE_ForRevision( $parentId, $expected ) {
		$instance = $this->constructPageInfoProviderInstance(
			[
				'wikiPage' => [],
				'revision' => [ 'getParentId' => $parentId ],
				'user'     => [],
			]
		);

		$this->assertEquals( $expected, $instance->isNewPage() );
	}

	/**
	 * @dataProvider parentIdProvider
	 */
	public function testWikiPage_TYPE_NEW_PAGE( $parentId, $expected ) {
		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getParentId' )
			->willReturn( $parentId );

		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$revisionGuard->expects( $this->any() )
			->method( 'newRevisionFromPage' )
			->willReturn( $revision );

		$instance = $this->constructPageInfoProviderInstance(
			[
				'wikiPage' => [ 'getRevisionRecord' => $revision ],
				'revision' => [],
				'user'     => [],
			]
		);

		$instance->setRevisionGuard(
			$revisionGuard
		);

		$this->assertEquals( $expected, $instance->isNewPage() );
	}

	public function parentIdProvider() {
		$provider = [
			[ 90001, false ],
			[ 0, true ],
			[ null, false ]
		];

		return $provider;
	}

	public function testWikiPage_TYPE_LAST_EDITOR() {
		$userPage = MockTitle::buildMock( 'Lula' );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_USER );

		$instance = $this->constructPageInfoProviderInstance(
			[
				'wikiPage' => [],
				'revision' => [],
				'user'     => [ 'getUserPage' => $userPage ],
			]
		);

		$this->assertEquals( $userPage, $instance->getLastEditor() );
	}

	public function constructPageInfoProviderInstance( array $parameters ) {
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $parameters['wikiPage'] as $method => $returnValue ) {
			$wikiPage->expects( $this->any() )
				->method( $method )
				->willReturn( $returnValue );
		}

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $parameters['revision'] as $method => $returnValue ) {
			$revision->expects( $this->any() )
				->method( $method )
				->willReturn( $returnValue );
		}

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $parameters['user'] as $method => $returnValue ) {
			$user->expects( $this->any() )
				->method( $method )
				->willReturn( $returnValue );
		}

		$pageInfoProvider = new PageInfoProvider(
			$wikiPage,
			( $parameters['revision'] !== [] ? $revision : null ),
			( $parameters['user'] !== [] ? $user : null )
		);

		if ( $parameters['revision'] != [] ) {
			$revisionLookup = $this->getMockBuilder( '\MediaWiki\Revision\RevisionLookup' )
				->disableOriginalConstructor()
				->getMock();

			$revisionLookup->expects( $this->any() )
				->method( 'getFirstRevision' )
				->willReturn( $revision );

			$pageInfoProvider->setRevisionLookup(
				$revisionLookup
			);
		}

		return $pageInfoProvider;
	}

	/**
	 * @dataProvider uploadStatusWikiFilePageDataProvider
	 */
	public function testWikiFilePage_TYPE_NEW_PAGE( $uploadStatus, $expected ) {
		$wikiFilePage = $this->getMockBuilder( '\WikiFilePage' )
			->disableOriginalConstructor()
			->getMock();

		if ( $uploadStatus !== null ) {
			// TODO: Illegal dynamic property (#5421)
			$wikiFilePage->smwFileReUploadStatus = $uploadStatus;
		}

		$instance = new PageInfoProvider( $wikiFilePage );

		$this->assertEquals( $expected, $instance->isNewPage() );
	}

	/**
	 * @dataProvider mediaTypeWikiFilePageDataProvider
	 */
	public function testWikiFilePage_MEDIA_TYPE( $file, $expected ) {
		$wikiFilePage = $this->getMockBuilder( '\WikiFilePage' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'isFilePage', 'getFile' ] )
			->getMock();

		$wikiFilePage->expects( $this->any() )
			->method( 'isFilePage' )
			->willReturn( true );

		$wikiFilePage->expects( $this->any() )
			->method( 'getFile' )
			->willReturn( $file );

		$instance = new PageInfoProvider( $wikiFilePage );

		$this->assertEquals( $expected, $instance->getMediaType() );
	}

	/**
	 * @dataProvider mimeTypeWikiFilePageDataProvider
	 */
	public function testWikiFilePage_MIME_TYPE( $file, $expected ) {
		$wikiFilePage = $this->getMockBuilder( '\WikiFilePage' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'isFilePage', 'getFile' ] )
			->getMock();

		$wikiFilePage->expects( $this->any() )
			->method( 'isFilePage' )
			->willReturn( true );

		$wikiFilePage->expects( $this->any() )
			->method( 'getFile' )
			->willReturn( $file );

		$instance = new PageInfoProvider( $wikiFilePage );

		$this->assertEquals( $expected, $instance->getMimeType() );
	}

	public function testWikiPage_MEDIA_TYPE() {
		$wikiFilePage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PageInfoProvider( $wikiFilePage );

		$this->assertNull( $instance->getMediaType() );
	}

	public function testWikiPage_MIME_TYPE() {
		$wikiFilePage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PageInfoProvider( $wikiFilePage );

		$this->assertNull( $instance->getMimeType() );
	}

	public function testWikiPage_NativeData() {
		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$content->expects( $this->any() )
			->method( 'getNativeData' )
			->willReturn( 'Foo' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( $content );

		$instance = new PageInfoProvider( $wikiPage );

		$this->assertEquals(
			'Foo',
			$instance->getNativeData()
		);
	}

	public function testWikiPage_NativeData_Null() {
		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->any() )
			->method( 'getContent' )
			->willReturn( null );

		$instance = new PageInfoProvider( $wikiPage );

		$this->assertSame(
			'',
			$instance->getNativeData()
		);
	}

	public function uploadStatusWikiFilePageDataProvider() {
		$provider = [
			[ null, false ],
			[ false, true ],
			[ true, false ]
		];

		return $provider;
	}

	public function mediaTypeWikiFilePageDataProvider() {
		$fileWithMedia = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$fileWithMedia->expects( $this->any() )
			->method( 'getMediaType' )
			->willReturn( 'FooMedia' );

		$fileNullMedia = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$fileNullMedia->expects( $this->any() )
			->method( 'getMediaType' )
			->willReturn( null );

		$provider[] = [ $fileWithMedia, 'FooMedia' ];
		$provider[] = [ $fileNullMedia, null ];

		return $provider;
	}

	public function mimeTypeWikiFilePageDataProvider() {
		$fileWithMime = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$fileWithMime->expects( $this->any() )
			->method( 'getMimeType' )
			->willReturn( 'FooMime' );

		$fileNullMime = $this->getMockBuilder( '\File' )
			->disableOriginalConstructor()
			->getMock();

		$fileNullMime->expects( $this->any() )
			->method( 'getMediaType' )
			->willReturn( null );

		$provider[] = [ $fileWithMime, 'FooMime' ];
		$provider[] = [ $fileNullMime, null ];

		return $provider;
	}

}
