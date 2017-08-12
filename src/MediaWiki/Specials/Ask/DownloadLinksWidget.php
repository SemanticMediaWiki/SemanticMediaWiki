<?php

namespace SMW\MediaWiki\Specials\Ask;

use SMW\Message;
use Html;
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

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'CSV' );
		$infolink->setParameter( 'csv', 'format' );
		$infolink->setParameter( 'CSV', 'searchlabel' );

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'RSS' );
		$infolink->setParameter( 'rss', 'format' );
		$infolink->setParameter( 'RSS', 'searchlabel' );

		$downloadLinks[] = $infolink->getHtml();

		$infolink->setCaption( 'RDF' );
		$infolink->setParameter( 'rdf', 'format' );
		$infolink->setParameter( 'RDF', 'searchlabel' );

		$downloadLinks[] = $infolink->getHtml();

		return Html::rawElement(
			'span',
			[
				'class' => 'smw-ask-downloadlinks'
			],
			'(' . implode( ' | ', $downloadLinks ) . ')'
		);
	}

}
