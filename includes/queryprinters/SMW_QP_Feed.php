<?php

/**
 * Result printer to export results as RSS/Atom feed
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.8
 *
 * @file
 * @ingroup SMWResultPrinter
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
class SMWFeedResultPrinter extends SMWExportPrinter {

	/**
	 * Returns human readable label for this printer
	 *
	 * @return string
	 */
	public function getName() {
		return wfMessage( 'smw-printername-feed' )->text();
	}

	/**
	 * @see SMWIExportPrinter::getMimeType
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string
	 */
	public function getMimeType( SMWQueryResult $queryResult ) {
		return $this->params['type'] === 'atom' ? 'application/atom+xml' : 'application/rss+xml';
	}

	/**
	 * @see SMWIExportPrinter::outputAsFile
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 * @param array $params
	 */
	public function outputAsFile( SMWQueryResult $queryResult, array $params ) {
		$this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );
	}

	/**
	 * File exports use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 *
	 * @param $mode
	 *
	 * @return integer
	 */
	public function getQueryMode( $mode ) {
		return $mode == SMWQueryProcessor::SPECIAL_PAGE ? SMWQuery::MODE_INSTANCES : SMWQuery::MODE_NONE;
	}

	/**
	 * Returns a string that is to be sent to the caller
	 *
	 * @param SMWQueryResult $res
	 * @param integer $outputMode
	 *
	 * @return string
	 */
	protected function getResultText( SMWQueryResult $res, $outputMode ) {

		if ( $outputMode == SMW_OUTPUT_FILE ) {
			if ( $res->getCount() == 0 ){
				$res->addErrors( array( wfMessage( 'smw_result_noresults' )->inContentLanguage()->text() ) );
				return '';
			}
			$result = $this->getFeed( $res, $this->params['type'] );
		} else {
			// Points to the Feed link
			$result = $this->getLink( $res, $outputMode )->getText( $outputMode, $this->mLinker );

			$this->isHTML = $outputMode == SMW_OUTPUT_HTML;
		}
		return $result;
	}

	/**
	 * Build a feed
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $results
	 * @param $type
	 *
	 * @return string
	 */
	protected function getFeed( SMWQueryResult $results, $type ){
		global $wgFeedClasses;

		if( !isset( $wgFeedClasses[$type] ) ) {
			$results->addErrors( array( wfMessage( 'feed-invalid' )->inContentLanguage()->text() ) );
			return '';
		}

		// Get feed class instance

		/**
		 * @var ChannelFeed $feed
		 */
		$feed = new $wgFeedClasses[$type](
			$this->feedTitle(),
			$this->feedDescription(),
			$this->feedURL()
		);

		// Create feed header
		$feed->outHeader();

		// Create feed items
		while ( $row = $results->getNext() ) {
			$feed->outItem( $this->feedItem( $row ) );
		}

		// Create feed footer
		$feed->outFooter();

		return $feed;
	}

	/**
	 * Returns feed title
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedTitle() {
		return $this->params['title'] === '' ? $GLOBALS['wgSitename'] : $this->params['title'];
	}

	/**
	 * Returns feed description
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedDescription() {
		return $this->params['description'] !== '' ? wfMessage( 'smw-label-feed-description', $this->params['description'], $this->params['type'] )->text() : wfMessage( 'tagline' )->text();
	}

	/**
	 * Returns feed URL
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	protected function feedURL() {
		return $GLOBALS['wgTitle']->getFullUrl();
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
		 * @var SMWResultArray $field
		 * @var SMWDataValue $object
		 */
		foreach ( $row as $field ) {
			$itemSegments = array();

			$subject = $field->getResultSubject()->getTitle();

			// Loop over all values for the property.
			while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {
				if ( $dataValue->getDataItem() instanceof SMWDIWikipage ) {
					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getLongHTMLText() );
				} else {
					$itemSegments[] = Sanitizer::decodeCharReferences( $dataValue->getWikiValue() );
				}
			}

			// Join all property values into a single string, separated by a comma
			if ( $itemSegments !== array() ) {
				$rowItems[] = implode( ', ', $itemSegments );
			}
		}

		if ( $subject instanceof Title ) {
			$wikiPage = WikiPage::newFromID( $subject->getArticleID() );

			if ( $wikiPage->exists() ){
				return new FeedItem(
					$subject->getPrefixedText(),
					$this->feedItemDescription( $rowItems, $this->getPageContent( $wikiPage ) ),
					$subject->getFullURL(),
					$wikiPage->getTimestamp(),
					$wikiPage->getUserText(),
					$this->feedItemComments()
				);
			}
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
		if ( in_array( $this->params['page'], array( 'abstract', 'full' ) ) ) {
			$parserOptions = new ParserOptions();
			$parserOptions->setEditSection( false );

			if ( $this->params['page'] === 'abstract' ) {
				// Abstract of the first 30 words
				// preg_match( '/^([^.!?\s]*[\.!?\s]+){0,30}/', $wikiPage->getText(), $abstract );
				// $text = $abstract[0] . ' ...';
			} else {
				if ( method_exists( $wikiPage, 'getContent' ) ) {
					$content = $wikiPage->getContent();

					if ( $content instanceof TextContent ) {
						$text = $content->getNativeData();
					}
					else {
						return '';
					}
				}
				else {
					$text = $wikiPage->getText();
				}
			}
			return $GLOBALS['wgParser']->parse( $text , $wikiPage->getTitle(), $parserOptions )->getText();
		} else {
			return '';
		}
	}


	/**
	 * Feed item description and property value output manipulation
	 *
	 * @since 1.8
	 *
	 * @param array $items
	 * @param string $pageContent
	 *
	 * @return string
	 */
	protected function feedItemDescription( $items, $pageContent  ) {
		return htmlspecialchars(
			FeedItem::stripComment( implode( ',', $items ) ) .
			FeedItem::stripComment( $pageContent )
		);
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

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['searchlabel']->setDefault( wfMessage( 'smw-label-feed-link' )->inContentLanguage()->text() );

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
			'values' => array( 'none', 'full' ), // @note Option abstract is not deployed with the 1.8 release
		);

		return $params;
	}
}