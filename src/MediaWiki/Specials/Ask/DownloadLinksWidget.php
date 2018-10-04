<?php

namespace SMW\MediaWiki\Specials\Ask;

use Html;
use SMW\Message;
use SMWInfolink as Infolink;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DownloadLinksWidget {

	/**
	 * @since 3.0
	 *
	 * @param Infolink|null $infolink
	 *
	 * @return string
	 */
	public static function downloadLinks( Infolink $infolink = null ) {

		if ( $infolink === null ) {
			return '';
		}

		// Avoid modifying the original object
		$infolink = clone $infolink;
		$downloadLinks = [];

		$infolink->setParameter( 'true', 'prettyprint' );
		$infolink->setParameter( 'true', 'unescape' );
		$infolink->setParameter( 'json', 'format' );
		$infolink->setParameter( 'JSON', 'searchlabel' );
		$infolink->setCaption( 'JSON' );

		$infolink->setLinkAttributes(
			[
				'title' => Message::get( [ 'smw-ask-download-link-desc', 'JSON' ], Message::TEXT, Message::USER_LANGUAGE ),
				'class' => 'page-link'
			]
		);

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'CSV' );
		$infolink->setParameter( 'csv', 'format' );
		$infolink->setParameter( 'CSV', 'searchlabel' );

		$infolink->setLinkAttributes(
			[
				'title' => Message::get( [ 'smw-ask-download-link-desc', 'CSV' ], Message::TEXT, Message::USER_LANGUAGE ),
				'class' => 'page-link'
			]
		);

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'RSS' );
		$infolink->setParameter( 'rss', 'format' );
		$infolink->setParameter( 'RSS', 'searchlabel' );

		$infolink->setLinkAttributes(
			[
				'title' => Message::get( [ 'smw-ask-download-link-desc', 'RSS' ], Message::TEXT, Message::USER_LANGUAGE ),
				'class' => 'page-link'
			]
		);

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'RDF' );
		$infolink->setParameter( 'rdf', 'format' );
		$infolink->setParameter( 'RDF', 'searchlabel' );

		$infolink->setLinkAttributes(
			[
				'title' => Message::get( [ 'smw-ask-download-link-desc', 'RDF' ], Message::TEXT, Message::USER_LANGUAGE ),
				'class' => 'page-link'
			]
		);

		$downloadLinks[] = $infolink->getHtml();

		return Html::rawElement(
			'div',
			[
				'id' => 'ask-export-links',
				'class' => 'smw-ask-downloadlinks export-links'
			],
			'<div class="smw-ui-pagination">' . implode( '', $downloadLinks ) . '</div>'
		);
	}

}
