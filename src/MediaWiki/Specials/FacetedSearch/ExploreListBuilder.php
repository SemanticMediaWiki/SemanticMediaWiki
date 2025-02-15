<?php

namespace SMW\MediaWiki\Specials\FacetedSearch;

use Html;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Message;
use SMW\Utils\UrlArgs;
use Title;

/**
 * @license GPL-2.0-or-later
 * @since   3.2
 *
 * @author mwjames
 */
class ExploreListBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var Profile
	 */
	private $profile;

	/**
	 * @since 3.2
	 *
	 * @param Profile $profile
	 */
	public function __construct( Profile $profile ) {
		$this->profile = $profile;
	}

	/**
	 * @since 3.2
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	public function buildHTML( Title $title ): string {
		$queryList = $this->profile->get( 'exploration.query_list', [] );
		$profileName = $this->profile->getProfileName();
		$html = '';

		foreach ( $queryList as $link ) {
			$urlArgs = new UrlArgs();
			$urlArgs->set( 'q', $link['query'] );
			$urlArgs->set( 'profile', $profileName );

			$description = '';

			if ( isset( $link['description'] ) ) {
				$description = Html::rawElement(
					'span',
					[
						'style' => 'margin-left:10px;'
					],
					$this->msg( [ 'smw-parse', $link['description'] ], Message::PARSE )
				);
			}

			$html .= Html::rawElement(
				'li',
				[],
				Html::rawElement(
					'a',
					[
						'href' => $title->getLocalUrl( $urlArgs )
					],
					$link['label']
				) . $description
			);
		}

		if ( $html === '' ) {
			return '';
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'explore-section'
			],
			$this->msg( 'smw-facetedsearch-explore-intro' ) . Html::rawElement(
				'ul',
				[
					'class' => 'explore-list'
				],
				$html
			)
		);
	}

}
