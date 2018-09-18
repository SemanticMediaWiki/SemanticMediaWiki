<?php

namespace SMW;

use Html;
use Onoi\Cache\Cache;
use ParserOutput;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;
use SMWQuery as Query;
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

	const POST_PROC_QUERYREF = 'smw-postproc-queryref';

	/**
	 * Specifies the TTL for the temporary tracking of a post edit
	 * update.
	 */
	const POST_UPDATE_TTL = 86400;

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
	 * @var []
	 */
	private $options = [];

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
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $options;
	}

	/**
	 * @since 3.0
	 *
	 * @return array|string
	 */
	public function getModules() {
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

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		$attributes = [
			'class' => 'smw-postproc',
			'data-subject' => $subject->getHash()
		];

		// Ensure to detect the post edit process to distinguish between an edit
		// event and any other post, get request in order to only sent a html
		// fragment once on the edit request and avoid an infinite loop when the
		// page is reloaded using an API request
		// @see Article::view
		$postEdit = $webRequest->getCookie(
			\EditPage::POST_EDIT_COOKIE_KEY_PREFIX . $title->getLatestRevID()
		);

		// Was the edit SMW specific or contains it an unrelated (e.g altered
		// some text unrelated to any property/value annotation) change?
		if ( $postEdit !== null && ( $changeDiff = ChangeDiff::fetch( $this->cache, $subject ) ) !== false ) {
			$postEdit = $this->checkDiff( $changeDiff );
		}

		// Is `@annotation` available as part of a #ask query?
		$refs = $this->parserOutput->getExtensionData( self::POST_PROC_QUERYREF );

		if ( $refs !== null && $refs !== [] ) {
			$postEdit = $this->checkRef( $title, $postEdit );
		}

		if ( $postEdit !== null && $refs !== null && $refs !== [] ) {
			$attributes['data-ref'] = json_encode( array_keys( $refs ) );
		}

		$jobs = [];

		if ( isset( $this->options['job.task'] ) ) {
			$jobs = $this->options['job.task'];

			// Not enabled, no need to invoke a job!
			if ( isset( $this->options['smwgEnabledQueryDependencyLinksStore'] ) && $this->options['smwgEnabledQueryDependencyLinksStore'] === false ) {
				unset( $jobs['smw.parserCachePurge'] );
			}

			if ( isset( $this->options['smwgEnabledFulltextSearch'] ) && $this->options['smwgEnabledFulltextSearch'] === false ) {
				unset( $jobs['smw.fulltextSearchTableUpdate'] );
			}
		}

		if ( $postEdit !== null && $jobs !== [] ) {
			$attributes['data-jobs'] = json_encode( $jobs );
		}

		// The element is only added temporarily in the event of a postEdit, a
		// reload of the page will not have the cookie being set and is therefore
		// neglected
		if ( $postEdit !== null ) {
			return Html::rawElement( 'div', $attributes );
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

		$data = $this->parserOutput->getExtensionData( self::POST_PROC_QUERYREF );

		if ( $data === null ) {
			$data = [];
		}

		$data[$queryRef] = true;

		$this->parserOutput->setExtensionData(
			self::POST_PROC_QUERYREF,
			$data
		);
	}

	private function checkRef( $title, $postEdit ) {

		$key = DependencyLinksUpdateJournal::makeKey( $title );

		// Is a postEdit, mark the update to avoid running in circles
		// when the pageCache is purged, use the latestRevID to distinguish
		// content changes
		if ( $postEdit !== null ) {

			$record = [
				$title->getLatestRevID() => true
			];

			$this->cache->save( $key . ':post', $record, self::POST_UPDATE_TTL );

			return $postEdit;
		}

		// Run outside of a postEdit, check if the dependency journal contains an
		// active reference to the article and run once (== hash that set by the
		// dependency journal which is == revID that initiated the change)
		$hash = $this->cache->fetch( $key );
		$record = $this->cache->fetch( $key . ':post' );

		if ( $hash !== false && ( $record === false || !isset( $record[$hash] ) ) ) {
			$postEdit = true;

			if ( !is_array( $record ) ) {
				$record = [];
			}

			$record[$hash] = true;

			// Add an update marker (1h) to avoid running twice in case the
			// journal reference hasn't been deleted yet as result of an existing
			// PostProcHandler update request.
			$this->cache->save( $key . ':post', $record, self::POST_UPDATE_TTL );
		}

		return $postEdit;
	}

	private function checkDiff( $changeDiff ) {

		$propertyList = $changeDiff->getPropertyList(
			'flip'
		);

		// Investigate whether the changeDiff contains a user invoked modification
		// and if so, allow the postEdit process to continue in order to act
		// on SMW data and not on text that doesn't involve changes to a property
		// value pair.
		foreach ( $changeDiff->getTableChangeOps() as $tableChangeOp ) {
			foreach ( $tableChangeOp->getFieldChangeOps() as $fieldChangeOp ) {
				$pid = $fieldChangeOp->get( 'p_id' );

				if ( !isset( $propertyList[$pid] ) ) {
					continue;
				}

				// Does the change involve an operation with a user defined
				// property?
				//
				// Some data were altered but since we cannot (within the request
				// framework and without further computation) anticipate whether
				// this influences a query or not, it is a good enough heuristic
				// to allow to continue the postProc.
				if ( $propertyList[$pid]{0} !== '_' ) {
					return true;
				}

				if ( $propertyList[$pid] === '_INST' || $propertyList[$pid] === '_ASK' ) {
					return true;
				}
			}
		}

		// Avoid any update since the condition of the diff containing any altered
		// SMW data was not meet.
		return null;
	}

}
