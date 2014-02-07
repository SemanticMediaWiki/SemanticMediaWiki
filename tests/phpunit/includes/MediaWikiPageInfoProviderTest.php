<?php

namespace SMW\Test;

use SMW\MediaWikiPageInfoProvider;

/**
 * @covers \SMW\MediaWikiPageInfoProvider
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MediaWikiPageInfoProviderTest extends SemanticMediaWikiTestCase {

	public function getClass() {
		return '\SMW\MediaWikiPageInfoProvider';
	}

	/**
	 * @return MediaWikiPageInfoProvider
	 */
	private function newInstance( $wikiPage = null, $revision = null, $user = null ) {

		if ( $wikiPage === null ) {
			$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage' );
		}

		return new MediaWikiPageInfoProvider( $wikiPage, $revision, $user );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 * @dataProvider instanceDataProvider
	 */
	public function testMethodAccess( array $parameters, $expected ) {

		$mockWikiPage = $parameters['wikiPage'];

		if ( is_array( $mockWikiPage ) ) {
			$mockWikiPage = $this->newMockBuilder()->newObject( 'WikiPage', $parameters['wikiPage'] );
		}

		$mockRevision = $this->newMockBuilder()->newObject( 'Revision', $parameters['revision'] );
		$mockUser = $this->newMockBuilder()->newObject( 'User', $parameters['user'] );

		$instance = $this->newInstance(
			$mockWikiPage,
			$parameters['revision'] ? $mockRevision : null,
			$parameters['user'] ? $mockUser : null
		);

		$method = $parameters['method'];

		$this->assertEquals(
			$expected['result'],
			$instance->{ $method }(),
			"Asserts that {$method} is accessible"
		);

	}

	/**
	 * @return array
	 */
	public function instanceDataProvider() {

		$provider = array();

		// #0 TYPE_MODIFICATION_DATE
		$provider[] = array(
			array(
				'wikiPage' => array( 'getTimestamp' => 1272508903 ),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getModificationDate'
			),
			array(
				'result' => 1272508903
			)
		);

		// #1 TYPE_CREATION_DATE
		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getTimestamp' => 1272508903
		) );

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_MAIN,
			'getFirstRevision' => $revision
		) );

		$subject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$provider[] = array(
			array(
				'wikiPage' => array( 'getTitle' => $title ),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getCreationDate'
			),
			array(
				'result' => 1272508903
			)
		);

		// #2 TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array( 'getParentId' => 9001 ),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => false
			)
		);

		// #3
		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array( 'getParentId' => null ),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => true
			)
		);

		// #4
		$mockRevisionWithNullParent = $this->newMockBuilder()->newObject( 'Revision', array(
			'getParentId' => null,
		) );

		$provider[] = array(
			array(
				'wikiPage' => array( 'getRevision' => $mockRevisionWithNullParent ),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => true
			)
		);

		// #5
		$mockRevision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getParentId' => 1009,
		) );

		$provider[] = array(
			array(
				'wikiPage' => array( 'getRevision' => $mockRevision ),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => false
			)
		);

		// #6
		$mockWikiFilePage = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true
		) );

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePage,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => false
			)
		);

		// #7
		$mockWikiFilePageWithStatusProperty = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true
		) );

		$mockWikiFilePageWithStatusProperty->smwFileReUploadStatus = false;

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePageWithStatusProperty,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => true
			)
		);

		// #8
		$mockWikiFilePageWithStatusProperty = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true
		) );

		$mockWikiFilePageWithStatusProperty->smwFileReUploadStatus = true;

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePageWithStatusProperty,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => false
			)
		);

		// #9
		$mockWikiFilePage = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true,
			'getFile'    => $this->newMockBuilder()->newObject( 'File', array( 'getMediaType' => 'FooMedia' ) )
		) );

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePage,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMediaType'
			),
			array(
				'result' => 'FooMedia'
			)
		);

		// #10
		$mockWikiFilePage = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true,
			'getFile'    => $this->newMockBuilder()->newObject( 'File' )
		) );

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePage,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMediaType'
			),
			array(
				'result' => null
			)
		);

		// #11
		$mockWikiFilePage = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true,
			'getFile'    => $this->newMockBuilder()->newObject( 'File', array( 'getMimeType' => 'FooMime' ) )
		) );

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePage,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMimeType'
			),
			array(
				'result' => 'FooMime'
			)
		);

		// #12
		$mockWikiFilePage = $this->newMockBuilder()->newObject( 'WikiFilePage', array(
			'isFilePage' => true,
			'getFile'    => $this->newMockBuilder()->newObject( 'File' )
		) );

		$provider[] = array(
			array(
				'wikiPage' => $mockWikiFilePage,
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMimeType'
			),
			array(
				'result' => null
			)
		);

		// #13
		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMimeType'
			),
			array(
				'result' => null
			)
		);

		// #14
		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array(),
				'user'     => array(),
				'method'   => 'getMediaType'
			),
			array(
				'result' => null
			)
		);

		// #15 TYPE_LAST_EDITOR
		$userPage = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_USER,
		) );

		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array(),
				'user'     => array( 'getUserPage' => $userPage ),
				'method'   => 'getLastEditor'
			),
			array(
				'result' => $userPage
			)
		);

		return $provider;
	}

}
