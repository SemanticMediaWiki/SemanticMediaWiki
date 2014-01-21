<?php

namespace SMW\Test;

use SMW\Store;
use SMW\ParserData;
use SMW\DIWikiPage;

use ParserOutput;
use WikiPage;
use Title;
use User;

use UnexpectedValueException;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class SemanticDataFetcher {

	protected $store = null;
	protected $title = null;

	/**
	 * @param Store $store
	 *
	 * @since 1.9.0.3
	 *
	 * @return SemanticDataFetcher
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
		return $this;
	}

	/**
	 * @param Title $title
	 *
	 * @since 1.9.0.3
	 *
	 * @return SemanticDataFetcher
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * @param Title $title
	 *
	 * @since 1.9.0.3
	 *
	 * @return SemanticData
	 */
	public function fetchFromStore() {
		return $this->getStore()->getSemanticData( DIWikiPage::newFromTitle( $this->getTitle() ) );
	}

	/**
	 * @param Title $title
	 *
	 * @since 1.9.0.3
	 *
	 * @return SemanticData
	 */
	public function fetchFromOutput() {
		$parserData = new ParserData( $this->getTitle(), $this->getOutput( $this->getTitle() ) );
		return $parserData->getData();
	}

	protected function getOutput( Title $title ) {

		$wikiPage = WikiPage::factory( $title );
		$revision = $wikiPage->getRevision();

		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( User::newFromId( $revision->getUser() ) ),
			$revision->getId()
		);

		return $parserOutput;
	}

	protected function getTitle() {

		if ( $this->title instanceOf Title ) {
			return $this->title;
		}

		throw new UnexpectedValueException( 'Missing a title object' );
	}

	protected function getStore() {

		if ( $this->store instanceOf Store ) {
			return $this->store;
		}

		throw new UnexpectedValueException( 'Missing a store object' );
	}

}
