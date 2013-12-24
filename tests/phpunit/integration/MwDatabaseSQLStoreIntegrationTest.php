<?php

namespace SMW\Test;

use SMW\StoreFactory;

use ContentHandler;
use WikiPage;
use Title;

/**
 * @covers \SMW\Store
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class MwDatabaseSQLStoreIntegrationTest extends \MediaWikiTestCase {

	/**
	 * @since 1.9
	 */
	public function getStore() {

		$store = StoreFactory::getStore();

		if ( !( $store instanceof \SMWSQLStore3 ) ) {
			$this->markTestSkipped( 'Test only applicable for SMWSQLStore3' );
		}

		return $store;
	}

	/**
	 * @dataProvider titleProvider
	 *
	 * @since 1.9
	 */
	public function testStoreRefreshDataOnDatabase( $ns, $name, $fragment, $iw, $useJobs ) {

		$store = $this->getStore();

		$title = Title::makeTitle( $ns, $name, $fragment, $iw );
		$wikiPage = new WikiPage( $title );
		$this->editPageAndFetchInfo( $wikiPage, __METHOD__ );

		$id = $title->getArticleId();
		$this->assertGreaterThan( 0, $store->refreshData( $id, 1, false, $useJobs ) );

		$this->deletePage( $wikiPage, __METHOD__ );

	}

	/**
	 * @since 1.9
	 */
	public function titleProvider( $title ) {

		$provider = array();

		$provider[] = array( NS_MAIN, __METHOD__ . '-withInterWiki', '', 'foo', false );
		$provider[] = array( NS_MAIN, __METHOD__ . '-normalTite', '', '', false );
		$provider[] = array( NS_MAIN, __METHOD__ . '-useUpdateJobs', '', '', true );

		return $provider;
	}

	/**
	 * @since 1.9
	 */
	protected function editPageAndFetchInfo( WikiPage $wikiPage, $on, $text = 'Foo' ) {

		$user = new MockSuperUser();

		if ( method_exists( 'WikiPage', 'doEditContent' ) ) {

			$content = ContentHandler::makeContent(
				$text,
				$wikiPage->getTitle(),
				CONTENT_MODEL_WIKITEXT
			);

			$wikiPage->doEditContent( $content, "testing " . $on, EDIT_NEW, false, $user );

			$content = $wikiPage->getRevision()->getContent();
			$format  = $content->getContentHandler()->getDefaultFormat();

			return $wikiPage->prepareContentForEdit( $content, null, $user, $format );

		}

		$wikiPage->doEdit( $text, "testing " . $on, EDIT_NEW, false, $user );

		return $wikiPage->prepareTextForEdit(
			$wikiPage->getRevision()->getRawText(),
			null,
			$user
		);
	}

	/**
	 * @since 1.9
	 */
	protected function deletePage( WikiPage $wikiPage, $on ) {

		if ( $wikiPage->exists() ) {
			$wikiPage->doDeleteArticle( $on .  " testing done" );
		}

	}

}