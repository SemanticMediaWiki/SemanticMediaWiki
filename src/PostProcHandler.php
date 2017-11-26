<?php

namespace SMW;

use SMWQuery as Query;
use SMW\DIWikiPage;
use Onoi\Cache\Cache;
use ParserOutput;
use Title;
use WebRequest;
use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;

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
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var boolean
	 */
	private $isEnabled = true;

	/**
	 * @since 3.0
	 *
	 * @param ParserOutput $parserOutput
	 * @param Cache $cache
	 */
	public function __construct( ParserOutput $parserOutput, Cache $cache ) {
		$this->parserOutput = $parserOutput;
		$this->cache = $cache;
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

		// Is `@annotation` available as part of a #ask query?
		$refs = $this->parserOutput->getExtensionData( self::PROC_POST_QUERYREF );

		if ( $this->isEnabled === false || $refs === null ) {
			return '';
		}

		// Ensure to detect the post edit process to distinguish between an edit
		// event and any other post, get request in order to only sent a html
		// fragment once on the edit request and avoid an infinite loop when the
		// page is reloaded using an API request
		// @see Article::view
		$postEdit = $webRequest->getCookie(
			\EditPage::POST_EDIT_COOKIE_KEY_PREFIX . $title->getLatestRevID()
		);

		$key = DependencyLinksUpdateJournal::makeKey(
			$title
		);

		// In case the dependency journal contains an active reference then
		// prepare for an additional update since `@annotation` is used to ensure
		// that values are recomputed without an explicit `edit` action.
		if ( $this->cache->fetch( $key ) && !$this->cache->contains( $key . ':post' ) ) {
			$postEdit = true;

			// Add a update marker (5 min.) to avoid running twice in case the
			// journal reference hasn't been deleted yet as result of an existing
			// PostProcHandler update request.
			$this->cache->save( $key . ':post', true, 300 );
		} else {
			$this->cache->delete( $key . ':post' );
		}

		// The element is only added temporary in the event of a postEdit, a
		// reload of the page will not have the cookie being set and is therefore
		// neglected
		if ( $postEdit !== null && $refs !== [] ) {
			return \Html::rawElement(
				'div',
				array(
					'class' => 'smw-postproc',
					'data-subject' => DIWikiPage::newFromTitle( $title )->getHash(),
					'data-ref' => json_encode( array_keys( $refs ) )
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
