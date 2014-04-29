<?php

namespace SMW\Cache;

use ObjectCache;
use Language;
use BagOStuff;
use ContextSource;
use RequestContext;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MessageCache {

	/** @var MessageCache[] */
	private static $instance = array();

	/** @var Language */
	protected $language = null;

	/** @var array */
	protected $messages = null;

	protected $touched = null;
	protected $cacheTimeOffset = null;

	protected $cache = null;
	protected $cacheType = null;

	protected $textFormat = null;

	/**
	 * @since 1.9.3
	 *
	 * @param Language $language
	 * @param integer|null $cacheTimeOffset
	 * @param integer|null $cacheType
	 */
	public function __construct( Language $language, $cacheTimeOffset = null, $cacheType = null ) {
		$this->language = $language;
		$this->cacheTimeOffset = $cacheTimeOffset;

		if ( $cacheType === null ) {
			$cacheType = $GLOBALS['smwgCacheType'];
		}

		$this->cacheType = $cacheType;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param Language $language
	 *
	 * @return MessageCache
	 */
	public static function ByLanguage( Language $language ) {

		$languageCode = $language->getCode();

		if ( !isset( self::$instance[ $languageCode ] ) ) {
			self::$instance[ $languageCode ] = new self( $language );
		}

		return self::$instance[ $languageCode ];
	}

	/**
	 * @since 1.9.3
	 *
	 * @return MessageCache
	 */
	public static function ByContentLanguage() {
		return self::ByLanguage( $GLOBALS['wgContLang'] );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param ContextSource|null $context
	 *
	 * @return MessageCache
	 */
	public static function ByContext( ContextSource $context = null ) {

		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		return self::ByLanguage( $context->getLanguage() );
	}

	/**
	 * @since 1.9.3
	 */
	public static function clear() {
		self::$instance = array();
	}

	/**
	 * @since 1.9.3
	 *
	 * @return MessageCache
	 */
	public function purge() {
		$this->getCache()->delete( $this->getCacheId() );
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param integer $cacheTimeOffset
	 *
	 * @return MessageCache
	 */
	public function setCacheTimeOffset( $cacheTimeOffset ) {
		$this->cacheTimeOffset = $cacheTimeOffset;
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param BagOStuff $cache
	 */
	public function setCache( BagOStuff $cache ) {
		$this->cache = $cache;
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return string
	 */
	public function getCacheId() {
		return $this->getCachePrefix() . ':smw:mcache:' . $this->language->getCode();
	}

	/**
	 * @since 1.9.3
	 *
	 * @return MessageText
	 */
	public function AsText() {
		$this->textFormat = null;
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return MessageText
	 */
	public function AsEscaped() {
		$this->textFormat = 'e';
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return MessageText
	 */
	public function AsParsed() {
		$this->textFormat = 'p';
		return $this;
	}

	/**
	 * @since 1.9.3
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function get( $key /* arguments */ ) {

		$arguments = func_get_args();

		if ( $this->messages === null ) {
			$this->messages = $this->fetchAllMessagesFromCache();
		}

		$key = $this->buildInternalKeyByArguments( $arguments );

		if ( isset( $this->messages[ $key ] ) ) {
			return $this->messages[ $key ];
		}

		return $this->getTextMessage( $key, $arguments );
	}

	protected function getTextMessage( $key, $arguments ) {

		$message = wfMessage( $arguments )->inLanguage( $this->language );

		switch ( $this->textFormat ) {
			case 'e':
				$this->messages[ $key ] = $message->escaped();
				break;
			case 'p':
				$this->messages[ $key ] = $message->parse();
				break;
			default:
				$this->messages[ $key ] = $message->text();
				break;
		}

		$this->textFormat = null;
		$this->updateAllMessagesToCache();

		return $this->messages[ $key ];
	}

	protected function updateAllMessagesToCache() {

		$messagesToBeCached = array(
			'touched'  => $this->getTouched(),
			'messages' => $this->messages
		);

		return $this->getCache()->set( $this->getCacheId(), $messagesToBeCached );
	}

	protected function fetchAllMessagesFromCache() {

		$cached = $this->getCache()->get( $this->getCacheId() );

		if ( isset( $cached['touched'] ) && isset( $cached['messages'] ) && $cached['touched'] === $this->getTouched() ) {
			return $cached['messages'];
		}

		return null;
	}

	protected function getCache() {

		if ( !$this->cache instanceOf BagOStuff ) {
			$this->cache = ObjectCache::getInstance( $this->cacheType );
		}

		return $this->cache;
	}

	protected function getTouched() {

		if ( $this->touched === null ) {
			$this->touched = $this->getMessageFileModificationTime() . $this->cacheTimeOffset;
		}

		return $this->touched;
	}

	protected function getMessageFileModificationTime() {

		if ( method_exists( $this->language, 'getJsonMessagesFileName' )  ) {
			return filemtime( $this->language->getJsonMessagesFileName( $this->language->getCode() ) );
		}

		return filemtime( $this->language->getMessagesFileName( $this->language->getCode() ) );
	}

	private function getCachePrefix() {
		return $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'];
	}

	private function buildInternalKeyByArguments( array $arguments ) {
		return implode( '#', $arguments ) . ( $this->textFormat ? '@' . $this->textFormat : null );
	}

}
