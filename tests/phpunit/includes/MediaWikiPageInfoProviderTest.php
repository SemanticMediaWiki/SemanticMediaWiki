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

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\MediaWikiPageInfoProvider';
	}

	/**
	 * @since 1.9
	 *
	 * @return MediaWikiPageInfoProvider
	 */
	private function newInstance( $wikiPage = null, $revision = null, $user = null ) {

		if ( $wikiPage === null ) {
			$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage' );
		}

		if ( $revision === null ) {
			$revision = $this->newMockBuilder()->newObject( 'Revision' );
		}

		if ( $user === null ) {
			$user = $this->newMockBuilder()->newObject( 'User' );
		}

		return new MediaWikiPageInfoProvider( $wikiPage, $revision, $user );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider instanceDataProvider
	 *
	 * @since 1.9
	 */
	public function testMethodAccess( array $setup, $expected ) {

		$method = $setup['method'];

		$instance = $this->newInstance(
			$this->newMockBuilder()->newObject( 'WikiPage', $setup['wikiPage'] ),
			$this->newMockBuilder()->newObject( 'Revision', $setup['revision'] ),
			$this->newMockBuilder()->newObject( 'User', $setup['user'] )
		);

		$this->assertEquals(
			$expected['result'],
			$instance->{ $method }(),
			"Asserts that {$method} returns an expected result"
		);

	}

	/**
	 * @return array
	 */
	public function instanceDataProvider() {

		$provider = array();

		// TYPE_MODIFICATION_DATE
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

		// TYPE_CREATION_DATE
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

		// TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'wikiPage' => array(),
				'revision' => array( 'getParentId' => 9001 ),
				'user'     => array(),
				'method'   => 'isNewPage'
			),
			array(
				'result' => true
			)
		);

		// TYPE_LAST_EDITOR
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
