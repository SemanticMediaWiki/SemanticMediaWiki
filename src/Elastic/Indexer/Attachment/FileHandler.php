<?php

namespace SMW\Elastic\Indexer\Attachment;

use ConfigException;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerAwareTrait;
use RequestContext;
use SMW\MediaWiki\FileRepoFinder;
use Title;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FileHandler {

	use LoggerAwareTrait;

	/**
	 * Transform the content to base64
	 */
	const FORMAT_BASE64 = 'format/base64';

	/**
	 * @var FileRepoFinder
	 */
	private $fileRepoFinder;

	/**
	 * @var callable
	 */
	private $readCallback;

	/**
	 * @since 3.2
	 *
	 * @param FileRepoFinder $fileRepoFinder
	 */
	public function __construct( FileRepoFinder $fileRepoFinder ) {
		$this->fileRepoFinder = $fileRepoFinder;
	}

	/**
	 * @since 3.2
	 *
	 * @param callable $readCallback
	 */
	public function setReadCallback( callable $readCallback ) {
		$this->readCallback = $readCallback;
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	public function findFileByTitle( Title $title ) {
		return $this->fileRepoFinder->findFile( $title );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public function fetchContentFromURL( string $url ) : string {

		//PHP 7.1+
		$readCallback = $this->readCallback;

		if ( $this->readCallback !== null ) {
			return $readCallback( $url );
		}

		$contents = '';

		// Avoid a "failed to open stream: HTTP request failed! HTTP/1.1 404 Not Found"
		$file_headers = @get_headers( $this->protocolizeUrl( $url ) );

		if (
			$file_headers !== false &&
			$file_headers[0] !== 'HTTP/1.1 404 Not Found' &&
			$file_headers[0] !== 'HTTP/1.0 404 Not Found' ) {
			return file_get_contents( $url );
		}

		$this->logger->info(
			[ 'File indexer', 'HTTP/1.1 404 Not Found', '{url}' ],
			[ 'method' => __METHOD__, 'role' => 'production', 'url' => $url ]
		);

		return $contents;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $contents
	 * @param string $type
	 *
	 * @return string
	 */
	public function format( string $contents, string $type = '' ) : string {

		if ( $type === self::FORMAT_BASE64 ) {
			return base64_encode( $contents );
		}

		return $contents;
	}

	/**
	 * Tries to add a scheme to the url, if a relative url was passed
	 *
	 * @since 4.0.0-alpha
	 *
	 * @param string $url
	 *
	 * @return string
	 */
    private function protocolizeUrl( string $url ): string {
        $parsed = parse_url( $url );

        if ( $parsed !== false && isset( $parsed['scheme'] ) ) {
            return $url;
        }

        try {
            $canonical = MediaWikiServices::getInstance()->getMainConfig()->get( 'CanonicalServer' );
            $parsed = parse_url( $canonical );

            if ( $parsed !== false && isset( $parsed['scheme'] ) ) {
                return $canonical;
            }
        } catch ( ConfigException $e ) {
            // Pass through
        }

        $mainContext = RequestContext::getMain();

        // Default scheme if RequestContext is null, in hope that it gets redirect if https is required
        $scheme = 'http';
        if ( $mainContext !== null && $mainContext->getRequest()->getProtocol() !== null ) {
            $scheme = $mainContext->getRequest()->getProtocol();
        }

        return sprintf( '%s://%s', $scheme, trim( $url, '/' ) );
    }
}
