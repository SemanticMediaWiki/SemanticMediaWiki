<?php

namespace SMW;

use SMWQuery as Query;
use ParserOutput;
use Title;
use WebRequest;

/**
 * Some updates require to be handled in a "post" process meaning after an update
 * has already taken place to iterate over those results as input for a value
 * dependency.
 *
 * The post process can only happen after the Store and hereby related processes
 * have been updated. A simple null edit is in most cases inappropriate and
 * therefore it is necessary to a complete a re-parse (triggered by the UpdateJob)
 * to ensure consistency among the stored and displayed data.
 *
 * The PostProc relies on an API request to initiate related updates and once
 * finished will handle the reload of the page.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PostProcHandler {

	const PROC_POST_QUERYREF = 'smw-postproc-queryref';

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @since 3.0
	 */
	public function __construct( ParserOutput $parserOutput ) {
		$this->parserOutput = $parserOutput;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isEnabled
	 */
	public function isEnabled( $isEnabled ) {
		$this->isEnabled = (bool)$isEnabled;
	}

	/**
	 * @since 3.0
	 *
	 * @return array|string
	 */
	public function getResModules() {
		return 'ext.smw.postproc';
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param WebRequest $webRequest
	 *
	 * @return string
	 */
	public function getHtml( Title $title, WebRequest $webRequest ) {

		if ( $this->isEnabled === false ) {
			return '';
		}

		// @see Article::view
		$key = \EditPage::POST_EDIT_COOKIE_KEY_PREFIX . $title->getLatestRevID();
		$postEdit = $webRequest->getCookie( $key );

		// Ensure to detect the post edit process to distinguish between an edit
		// event and any other post, get request in order to only sent a html
		// fragment once on the edit request and avoid an infinite loop when the
		// page is reloaded using an API request

		// The element is only added temporary in the event of a postEdit, a
		// reload of the page will not have the cookie being set and is therefore
		// neglected
		if ( $postEdit !== null && ( $refs = $this->parserOutput->getExtensionData( self::PROC_POST_QUERYREF ) ) !== null ) {
			return \Html::rawElement(
				'div',
				array(
					'class' => 'smw-postproc',
					'data-subject' => DIWikiPage::newFromTitle( $title )->getHash(),
					'data-queryref' => json_encode( array_keys( $refs ) )
				),
				'' // Message::get( 'smw-postproc-queryref', Message::PARSE )
			);
		}

		return '';
	}

	/**
	 * @since 3.0
	 *
	 * @param Query $query
	 */
	public function addQueryRef( Query $query ) {

		// Query:getHash returns a hash based on a fingerprint
		// (when $smwgQueryResultCacheType is set) that eliminates duplicate
		// queries, yet for the post processing it is necessary to know each
		// single query (same-condition, different printout) to allow running
		// alternating updates as in case of cascading value dependencies
		$queryRef = HashBuilder::createFromArray( $query->toArray() );

		$data = $this->parserOutput->getExtensionData( self::PROC_POST_QUERYREF );

		if ( $data === null ) {
			$data = array();
		}

		$data[$queryRef] = true;

		$this->parserOutput->setExtensionData(
			self::PROC_POST_QUERYREF,
			$data
		);
	}

}
