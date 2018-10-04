<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\MediaWiki\TitleFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleAugmentor {

	/**
	 * @var TitleFactory
	 */
	private $titleFactory;

	/**
	 * @since 3.0
	 *
	 * @param TitleFactory $titleFactory
	 */
	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$res
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function augment( array &$res, array $parameters ) {

		if ( !isset( $res['query'] ) && $res['query'] === [] ) {
			return;
		}

		if ( isset( $parameters['fullText' ] ) || isset( $parameters['fullURL' ] ) ) {

			foreach ( $res['query'] as $key => &$value ) {

				$title = $this->titleFactory->newFromID( $value['id'] );

				if ( isset( $parameters['fullText' ] ) ) {
					$value['fullText'] = $title->getFullText();
				}

				if ( isset( $parameters['fullURL' ] ) ) {
					$value['fullURL'] = $title->getFullURL();
				}
			}
		}

		// Remove the internal ID, no external consumer should rely on it
		foreach ( $res['query'] as $key => &$value ) {
			unset( $value['id'] );
		}

		return $res;
	}

}
