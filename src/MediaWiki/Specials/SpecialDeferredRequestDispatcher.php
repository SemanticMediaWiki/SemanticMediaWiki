<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SpecialPage;
use Title;

/**
 * This class is the receiving endpoint for the `DeferredRequestDispatchManager` invoked
 * job request.
 *
 * This special page is not expected to interact with a user and therefore it is
 * unlisted.
 *
 * @license GNU GPL v2+
 * @since   2.3
 *
 * @author mwjames
 */
class SpecialDeferredRequestDispatcher extends SpecialPage {

	/**
	 * @var boolean
	 */
	private $allowedToModifyHttpHeader = true;

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'DeferredRequestDispatcher', '', false );
	}

	/**
	 * SpecialPage::doesWrites
	 *
	 * @return boolean
	 */
	public function doesWrites() {
		return true;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'maintenance';
	}

	/**
	 * Only used during unit testing
	 *
	 * @since 2.3
	 */
	public function disallowToModifyHttpHeader() {
		$this->allowedToModifyHttpHeader = false;
	}

	/**
	 * @since 2.3
	 *
	 * @return string
	 */
	public static function getTargetURL() {
		return SpecialPage::getTitleFor( 'DeferredRequestDispatcher')->getFullURL();
	}

	/**
	 * @since 2.3
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function getRequestToken( $key ) {
		return md5( $key . $GLOBALS['wgSecretKey'] );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {

		$this->getOutput()->disable();

		if ( wfReadOnly() ) {
			return $this->modifyHttpHeader( "HTTP/1.0 423 Locked", 'Wiki is in read-only mode.' );
		}

		if ( !$this->isHttpRequestMethod( 'HEAD' ) && !$this->isHttpRequestMethod( 'POST' ) ) {
			return $this->modifyHttpHeader( "HTTP/1.0 400 Bad Request", 'The special page requires a POST/HEAD request.' );
		}

		$parameters = json_decode(
			$this->getRequest()->getVal( 'parameters' ),
			true
		);

		if ( $this->isHttpRequestMethod( 'POST' ) && self::getRequestToken( $parameters['timestamp'] ) !== $parameters['requestToken'] ) {
			return $this->modifyHttpHeader( "HTTP/1.0 400 Bad Request", 'Invalid or staled requestToken was provided for the request' );
		}

		$this->modifyHttpHeader( "HTTP/1.0 202 Accepted" );

		if ( !isset( $parameters['async-job'] ) ) {
			return;
		}

		return $this->doRunJob( $parameters, ApplicationFactory::getInstance()->getMediaWikiLogger() );
	}

	private function modifyHttpHeader( $header, $message = '' ) {

		if ( !$this->allowedToModifyHttpHeader ) {
			return null;
		}

		ignore_user_abort( true );
		header( $header );
		print $message;
		ob_flush();
		flush();

		// @see SpecialRunJobs
		// MW 1.27 / https://phabricator.wikimedia.org/T115413
		// Once the client receives this response, it can disconnect
		set_error_handler( function ( $errno, $errstr ) {
			if ( strpos( $errstr, 'Cannot modify header information' ) !== false ) {
				return true; // bug T115413
			}
			// Delegate unhandled errors to the default handlers
			return false;
		} );
	}

	private function doRunJob( $parameters, $logger ) {

		$type = $parameters['async-job']['type'];
		$title = Title::newFromDBkey( $parameters['async-job']['title'] );

		if ( $title === null ) {
			return $logger->info( __METHOD__  . " invalid title" );
		}

		$logger->info( __METHOD__ . ' ' . $type . ' :: ' .  $title->getPrefixedDBkey() . '#' . $title->getNamespace() );

		$job = ApplicationFactory::getInstance()->newJobFactory()->newByType(
			$type,
			$title,
			$parameters
		);

		$job->run();

		return true;
	}

	// 1.19 doesn't have a getMethod
	private function isHttpRequestMethod( $key ) {

		if ( method_exists( $this->getRequest(), 'getMethod') ) {
			return $this->getRequest()->getMethod() == $key;
		}

		return isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] == $key : false;
	}

}
