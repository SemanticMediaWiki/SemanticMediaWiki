<?php

namespace SMW\Query\ResultPrinters;

use SMWQueryResult as QueryResult;
use SMW\Query\ExportPrinter;
use SMW\Site;
use FeedItem;
use ParserOptions;
use Sanitizer;
use SMWDIWikiPage;
use TextContent;
use Title;
use WikiPage;

/**
 * Result printer that exports query results as RSS/Atom feed
 *
 * @since 1.8
 *
 * @license GNU GPL v2 or later
 * @author mwjames
 */
final class FeedExportPrinter extends ResultPrinter implements ExportPrinter {

	/**
	 * @var boolean
	 */
	private $httpHeader = true;

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'smw-printername-feed' )->text();
	}

	/**
	 * @see ExportPrinter::isExportFormat
	 *
	 * {@inheritDoc}
	 */
	public function isExportFormat() {
		return true;
	}

	/**
	 * @see 3.0
	 */
	public function disableHttpHeader() {
		$this->httpHeader = false;
	}

	/**
	 * @see ExportPrinter::getMimeType
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return $this->params['type'] === 'atom' ? 'application/atom+xml' : 'application/rss+xml';
	}

	/**
	 * @see ExportPrinter::getFileName
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {
		return false;
	}

	/**
	 * @see ExportPrinter::outputAsFile
	 *
	 * {@inheritDoc}
	 */
	public function outputAsFile( QueryResult $queryResult, array $params ) {
		$result = $this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );

		if ( Site::isCommandLineMode() ) {

			if ( $this->httpHeader ) {
				header( 'Content-type: ' . 'text/xml' . '; charset=UTF-8' );
			}

			echo $result;
		}
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( $this->msg( 'smw-label-feed-link' )->inContentLanguage()->text() );

		$params['type'] = array(
			'type' => 'string',
			'default' => 'rss',
			'message' => 'smw-paramdesc-feedtype',
			'values' => array( 'rss', 'atom' ),
		);

		$params['title'] = array(
			'message' => 'smw-paramdesc-feedtitle',
			'default' => '',
			'aliases' => array( 'rsstitle' ),
		);

		$params['description'] = array(
			'message' => 'smw-paramdesc-feeddescription',
			'default' => '',
			'aliases' => array( 'rssdescription' ),
		);

		$params['page'] = array(
			'message' => 'smw-paramdesc-feedpagecontent',
			'default' => 'none',
			'values' => array( 'none', 'full', 'abstract' ),
		);

		return $params;
	}

	/**
	 * @since 2.5
	 * @see ResultPrinter::getDefaultSort
	 *
	 * {@inheritDoc}
	 */
	public function getDefaultSort() {
		return 'DESC';
	}

	/**
	 * Returns a string that is to be sent to the caller
	 *
	 * @param QueryResult $res
	 * @param integer $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getFeedLink( $res, $outputMode );
		}

		if ( $res->getCount() == 0 ){
			$res->addErrors( array( $this->msg( 'smw_result_noresults' )->inContentLanguage()->text() ) );
		}

		return $this->getFeed( $res, $this->params['type'] );
	}

	/**
	 * Build a feed
	 *
	 * @since 1.8
	 *
	 * @param QueryResult $results
	 * @param $type
	 *
	 * @return string
	 */
	protected function getFeed( QueryResult $results, $type ) {
		global $wgFeedClasses;

		if( !isset( $wgFeedClasses[$type] ) ) {
			$results->addErrors( array( $this->msg( 'feed-invalid' )->inContentLanguage()->text() ) );
			return '';
		}

		/**
		 * @var \ChannelFeed $feed
		 */
		$feed = new $wgFeedClasses[$type](
			$this->feedTitle(),
			$this->feedDescription(),
			$this->feedURL()
		);

		// Create feed header
		if ( $this->httpHeader ) {
			$feed->outHeader();
		}

		// Create feed items
		while ( $row = $results->getNext() ) {
			$feed->outItem( $this->feedItem( $row ) );
		}

		// Create feed footer
		$feed->outFooter();
	}

	/**
	 * Returns feed title
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedTitle() {

		if ( $this->params['title'] === '' ) {
			return $GLOBALS['wgSitename'];
		}

		return $this->params['title'];
	}

	/**
	 * Returns feed description
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedDescription() {

		if ( $this->params['description'] !== '' ) {
			return $this->msg( 'smw-label-feed-description', $this->params['description'], $this->params['type'] )->text();
		}

		return $this->msg( 'tagline' )->text();
	}

	/**
	 * Returns feed URL
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedURL() {

		if (  $GLOBALS['wgTitle'] instanceof Title ) {
			return $GLOBALS['wgTitle']->getFullUrl();
		}

		return Title::newFromText( 'Feed' )->getFullUrl();
	}

	/**
	 * Returns feed item
	 *
	 * @since 1.8
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	protected function feedItem( array $row ) {

		$rowItems = array();
		$subject = false;

		/**
		 * Loop over all properties within a row
		 *
		 * @var \SMWResultArray $field
		 * @var \SMWDataValue $object
		 */
		foreach ( $row as $field ) {
			$itemSegments = array();

			$subject = $field->getResultSubject()->getTitle();

			// Loop over all values for the property.
			while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
				if ( $dataValue->getDataItem() instanceof SMWDIWikipage ) {

					$linker = null;

					if ( $dataValue->getDataItem()->getSubobjectName() === '' && $this->params['link'] !== 'none' ) {
						$linker = smwfGetLinker();
					}

					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getLongWikiText( $linker ) );
				} else {
					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getWikiValue() );
				}
			}

			// Join all property values into a single string, separated by a comma
			if ( $itemSegments !== array() ) {
				$rowItems[] = $this->parse( $subject, implode( ', ', $itemSegments ) );
			}
		}

		if ( $subject instanceof Title ) {
			return $this->newFeedItem( $subject, $rowItems );
		}

		return array();
	}

	/**
	 * Returns page content
	 *
	 * @since 1.8
	 *
	 * @param WikiPage $wikiPage
	 *
	 * @return string
	 */
	protected function getPageContent( WikiPage $wikiPage ) {

		if ( !in_array( $this->params['page'], array( 'abstract', 'full' ) ) ) {
			return '';
		}

		if ( method_exists( $wikiPage, 'getContent' ) ) {
			$content = $wikiPage->getContent();

			if ( $content instanceof TextContent ) {
				$text = $content->getNativeData();
			} else {
				return '';
			}
		} else {
			$text = $wikiPage->getText();
		}

		return $this->parse( $wikiPage->getTitle(), $text );
	}

	/**
	 * Feed item description and property value output manipulation
	 *
	 * @note FeedItem will do an FeedItem::xmlEncode therefore no need
	 * to be overly cautious here
	 *
	 * @since 1.8
	 *
	 * @param array $items
	 * @param string $pageContent
	 *
	 * @return string
	 */
	protected function feedItemDescription( $items, $pageContent  ) {

		$text = FeedItem::stripComment( implode( '', $items ) ) . FeedItem::stripComment( $pageContent );

		// Abstract of the first 200 chars
		if ( $this->params['page'] === 'abstract' ) {
			$text = preg_replace('/\s+?(\S+)?$/', '', substr( $text, 0, 201 ) ) . ' ...';
		}

		return $text;
	}

	/**
	 * According to MW documentation, the comment field is only implemented for RSS
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedItemComments( ) {
		return '';
	}

	private function newFeedItem( $subject, $rowItems ) {
		$wikiPage = WikiPage::newFromID( $subject->getArticleID() );

		if ( $wikiPage !== null && $wikiPage->exists() ){
			$feedItem = new FeedItem(
				$subject->getPrefixedText(),
				$this->feedItemDescription( $rowItems, $this->getPageContent( $wikiPage ) ),
				$subject->getFullURL(),
				$wikiPage->getTimestamp(),
				$wikiPage->getUserText(),
				$this->feedItemComments()
			);
		} else {
			// #1562
			$feedItem = new FeedItem(
				$subject->getPrefixedText(),
				'',
				$subject->getFullURL()
			);
		}

		return $feedItem;
	}

	private function parse( Title $title = null, $text ) {

		if ( $title === null ) {
			return $text;
		}

		$parserOptions = new ParserOptions();

		// FIXME: Remove the if block once compatibility with MW <1.31 is dropped
		if ( ! defined( '\ParserOutput::SUPPORTS_STATELESS_TRANSFORMS' ) || \ParserOutput::SUPPORTS_STATELESS_TRANSFORMS !== 1 ) {
			$parserOptions->setEditSection( false );
		}

		return $GLOBALS['wgParser']->parse( $text, $title, $parserOptions )->getText( [ 'enableSectionEditLinks' => false ] );
	}

	private function getFeedLink( QueryResult $res, $outputMode ) {

		// Can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputMode == SMW_OUTPUT_HTML;

		$link = $this->getLink(
			$res,
			$outputMode
		);

		return $link->getText( $outputMode, $this->mLinker );
	}

}
