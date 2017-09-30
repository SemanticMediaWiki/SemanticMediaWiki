<?php

namespace SMW\MediaWiki\Api\Browse;

use SMW\MediaWiki\TitleCreator;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleAugmentor {

	/**
	 * @var TitleCreator
	 */
	private $titleCreator;

	/**
	 * @since 3.0
	 *
	 * @param TitleCreator $titleCreator
	 */
	public function __construct( TitleCreator $titleCreator ) {
		$this->titleCreator = $titleCreator;
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

				$title = $this->titleCreator->newFromID( $value['id'] );

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
