<?php

namespace SMW\Test;

use SMW\Store;
use SMW\ParserData;

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
 * @since 1.9.1
 *
 * @author mwjames
 */
class ByPageSemanticDataFinder {

	protected $store = null;
	protected $title = null;

	/**
	 * @since 1.9.1
	 *
	 * @param Store $store
	 *
	 * @return PageDataFetcher
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
		return $this;
	}

	/**
	 * @since 1.9.1
	 *
	 * @param Title $title
	 *
	 * @return PageDataFetcher
	 */
	public function setTitle( Title $title ) {
		$this->title = $title;
		return $this;
	}

	/**
	 * @since 1.9.1
	 *
	 * @return SemanticData
	 */
	public function fetchFromStore() {
		return $this->getStore()->getSemanticData( $this->getPageData()->getSubject() );
	}

	/**
	 * @since 1.9.1
	 *
	 * @return SemanticData
	 */
	public function fetchFromOutput() {
		return $this->getPageData()->getData();
	}

	protected function getPageData() {
		return new ParserData( $this->getTitle(), $this->makeOutputFromPageRevision() );
	}

	protected function getPage() {
		return WikiPage::factory( $this->getTitle() );
	}

	protected function makeOutputFromPageRevision() {

		$wikiPage = $this->getPage();
		$revision = $wikiPage->getRevision();

		$parserOutput = $wikiPage->getParserOutput(
			$wikiPage->makeParserOptions( User::newFromId( $revision->getUser() ) ),
			$revision->getId()
		);

		if ( $parserOutput instanceOf ParserOutput ) {
			return $parserOutput;
		}

		throw new UnexpectedValueException( 'Expected a ParserOutput object' );
	}

	protected function getTitle() {

		if ( $this->title instanceOf Title ) {
			return $this->title;
		}

		throw new UnexpectedValueException( 'Expected a Title object' );
	}

	protected function getStore() {

		if ( $this->store instanceOf Store ) {
			return $this->store;
		}

		throw new UnexpectedValueException( 'Expected a Store object' );
	}

}
