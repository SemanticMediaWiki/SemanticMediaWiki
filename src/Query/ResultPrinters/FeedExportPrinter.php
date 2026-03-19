<?php

namespace SMW\Query\ResultPrinters;

use MediaWiki\Content\TextContent;
use MediaWiki\Context\RequestContext;
use MediaWiki\Feed\ChannelFeed;
use MediaWiki\Feed\FeedItem;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\Query\ExportPrinter;
use SMW\Query\Query;
use SMW\Query\QueryProcessor;
use SMW\Query\QueryResult;
use SMW\Query\Result\ResultArray;
use SMW\Query\Result\StringResult;
use SMW\Site;
use WikiPage as MWWikiPage;

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
	 * @var bool
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
	public function isExportFormat(): bool {
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
	public function getFileName( QueryResult $queryResult ): bool {
		return false;
	}

	/**
	 * @see ExportPrinter::outputAsFile
	 *
	 * {@inheritDoc}
	 */
	public function outputAsFile( QueryResult $queryResult, array $params ) {
		$result = $this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );

		if ( Site::isCommandLineMode() || $queryResult instanceof StringResult ) {

			if ( $this->httpHeader ) {
				header( 'Content-type: ' . 'text/xml' . '; charset=UTF-8' );
			}

			echo $result;
		}
	}

	/**
	 * The export uses MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) otherwise use MODE_NONE (displaying just a
	 * download link).
	 *
	 * @param $mode
	 *
	 * @return int
	 */
	public function getQueryMode( $mode ): int {
		if ( $mode == QueryProcessor::SPECIAL_PAGE ) {
			return Query::MODE_INSTANCES;
		}
		return Query::MODE_NONE;
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( $this->msg( 'smw-label-feed-link' )->inContentLanguage()->text() );

		$params['type'] = [
			'type' => 'string',
			'default' => 'rss',
			'message' => 'smw-paramdesc-feedtype',
			'values' => [ 'rss', 'atom' ],
		];

		$params['title'] = [
			'message' => 'smw-paramdesc-feedtitle',
			'default' => '',
			'aliases' => [ 'rsstitle' ],
		];

		$params['description'] = [
			'message' => 'smw-paramdesc-feeddescription',
			'default' => '',
			'aliases' => [ 'rssdescription' ],
		];

		$params['page'] = [
			'message' => 'smw-paramdesc-feedpagecontent',
			'default' => 'none',
			'values' => [ 'none', 'full', 'abstract' ],
		];

		return $params;
	}

	/**
	 * @since 2.5
	 * @see ResultPrinter::getDefaultSort
	 *
	 * {@inheritDoc}
	 */
	public function getDefaultSort(): string {
		return 'DESC';
	}

	/**
	 * Returns a string that is to be sent to the caller
	 *
	 * @param QueryResult $res
	 * @param int $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( QueryResult $res, $outputMode ) {
		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getFeedLink( $res, $outputMode );
		}

		if ( $res->getCount() == 0 ) {
			$res->addErrors( [ $this->msg( 'smw_result_noresults' )->inContentLanguage()->text() ] );
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

		if ( !isset( $wgFeedClasses[$type] ) ) {
			$results->addErrors( [ $this->msg( 'feed-invalid' )->inContentLanguage()->text() ] );
			return '';
		}

		/**
		 * @var ChannelFeed $feed
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
		$row = $results->getNext();
		while ( $row ) {
			$feed->outItem( $this->feedItem( $row ) );
			$row = $results->getNext();
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
		if ( $GLOBALS['wgTitle'] instanceof Title ) {
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
		$rowItems = [];
		$subject = false;

		/**
		 * Loop over all properties within a row
		 *
		 * @var ResultArray $field
		 * @var DataValue $object
		 */
		foreach ( $row as $field ) {
			$itemSegments = [];

			$subject = $field->getResultSubject()->getTitle();

			// Loop over all values for the property.
			$dataValue = $field->getNextDataValue();
			while ( $dataValue !== false ) {
				if ( $dataValue->getDataItem() instanceof WikiPage ) {

					$linker = null;

					if ( $dataValue->getDataItem()->getSubobjectName() === '' && $this->params['link'] !== 'none' ) {
						$linker = smwfGetLinker();
					}

					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getLongWikiText( $linker ) );
				} else {
					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getWikiValue() );
				}
				$dataValue = $field->getNextDataValue();
			}

			// Join all property values into a single string, separated by a comma
			if ( $itemSegments !== [] ) {
				$rowItems[] = $this->parse( $subject, implode( ', ', $itemSegments ) );
			}
		}

		if ( $subject instanceof Title ) {
			return $this->newFeedItem( $subject, $rowItems );
		}

		return [];
	}

	/**
	 * Returns page content
	 *
	 * @since 1.8
	 *
	 * @param MWWikiPage $wikiPage
	 *
	 * @return string
	 */
	protected function getPageContent( MWWikiPage $wikiPage ) {
		if ( !in_array( $this->params['page'], [ 'abstract', 'full' ] ) ) {
			return '';
		}

		$content = $wikiPage->getContent();

		if ( $content instanceof TextContent ) {
			$text = $content->getNativeData();
		} else {
			return '';
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
	protected function feedItemDescription( $items, $pageContent ) {
		$text = FeedItem::stripComment( implode( '', $items ) ) . FeedItem::stripComment( $pageContent );

		// Abstract of the first 200 chars
		if ( ( $this->params['page'] ?? '' ) === 'abstract' ) {
			$text = preg_replace( '/\s+?(\S+)?$/', '', substr( $text, 0, 201 ) ) . ' ...';
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
	protected function feedItemComments(): string {
		return '';
	}

	private function newFeedItem( $title, $rowItems ) {
		$mwServices = MediaWikiServices::getInstance();
		$wikiPage = $mwServices->getWikiPageFactory()->newFromID( $title->getArticleID() );

		if ( $wikiPage !== null && $wikiPage->exists() ) {

			// #1741
			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				Wikipage::newFromTitle( $title )
			);

			// Ensures that the namespace prefix (Help:...) is used in cases where
			// no display title is available.
			$dataValue->setOption( 'prefixed.preferred.caption', true );

			$feedItem = new FeedItem(
				$dataValue->getPreferredCaption(),
				$this->feedItemDescription( $rowItems, $this->getPageContent( $wikiPage ) ),
				$title->getFullURL(),
				$wikiPage->getTimestamp(),
				$wikiPage->getUserText(),
				$this->feedItemComments()
			);
		} else {
			// #1562
			$feedItem = new FeedItem(
				$title->getPrefixedText(),
				'',
				$title->getFullURL()
			);
		}

		return $feedItem;
	}

	private function parse( ?Title $title, $text ) {
		if ( $title === null ) {
			return $text;
		}

		$user = RequestContext::getMain()->getUser();
		$parserOptions = new ParserOptions( $user );
		$parserOptions->setSuppressSectionEditLinks( true );

		return MediaWikiServices::getInstance()
			->getParser()->parse( $text, $title, $parserOptions )->getContentHolderText();
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
