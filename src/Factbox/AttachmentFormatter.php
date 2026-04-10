<?php

namespace SMW\Factbox;

use MediaWiki\Html\Html;
use SMW\DataItems\Blob;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataValueFactory;
use SMW\Localizer\Message;
use SMW\PropertyRegistry;
use SMW\Store;
use SMW\Utils\HtmlTable;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AttachmentFormatter {

	private HtmlTable $htmlTable;

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function buildHTML( array $attachments = [] ): string {
		if ( $attachments === [] ) {
			return '';
		}

		$dataValueFactory = DataValueFactory::getInstance();

		$property = new Property( '_ATTCH_LINK' );
		$this->htmlTable = new HtmlTable();

		foreach ( $attachments as $dataItem ) {
			$this->buildRow( $property, $dataItem );
		}

		$propertyRegistry = PropertyRegistry::getInstance();

		$mime = $dataValueFactory->newDataValueByItem(
			( new Property( '_MIME' ) )->getDiWikiPage()
		);

		$mime->setOption( $mime::SHORT_FORM, true );
		$mime->setOutputFormat( 'LOCL' );

		$mime->setCaption(
			$propertyRegistry->findPropertyLabelFromIdByLanguageCode(
				'_MIME',
				$mime->getOption( $mime::OPT_USER_LANGUAGE )
			)
		);

		$mdat = $dataValueFactory->newDataValueByItem(
			( new Property( '_MDAT' ) )->getDiWikiPage()
		);

		$mdat->setOption( $mime::SHORT_FORM, true );
		$mdat->setOutputFormat( 'LOCL' );

		$mdat->setCaption(
			$propertyRegistry->findPropertyLabelFromIdByLanguageCode(
				'_MDAT',
				$mdat->getOption( $mdat::OPT_USER_LANGUAGE )
			)
		);

		$isLocalMsg = Message::get(
			'smw-factbox-attachments-is-local',
			Message::TEXT,
			Message::USER_LANGUAGE
		);

		$this->htmlTable->header( '&nbsp;' );
		$this->htmlTable->header( $mime->getShortWikiText() );
		$this->htmlTable->header( $mdat->getShortWikiText(), );
		$this->htmlTable->header( $isLocalMsg );

		return Html::rawElement(
			'div',
			[ 'class' => 'smw-factbox-table-wrapper' ],
			$this->htmlTable->table( [
				'id' => 'smw-factbox-attachments',
				'class' => 'wikitable sortable'
			] )
		);
	}

	private function buildRow( Property $property, $dataItem ): void {
		$unknown = Message::get(
			'smw-factbox-attachments-value-unknown',
			Message::TEXT,
			Message::USER_LANGUAGE
		);

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$property
		);

		$dataValue->setOption( $dataValue::NO_IMAGE, true );
		$attachment = $dataValue->getShortWikiText( true ) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI );

		$this->htmlTable->cell( $attachment );

		$pv = $this->store->getPropertyValues( $dataItem, new Property( '_MIME' ) );
		$pv = is_array( $pv ) ? end( $pv ) : '';

		$this->htmlTable->cell(
			$pv instanceof Blob ? $pv->getString() : $unknown
		);

		$prop = new Property( '_MDAT' );
		$text = $unknown;

		$pv = $this->store->getPropertyValues( $dataItem, $prop );
		$pv = is_array( $pv ) ? end( $pv ) : '';

		if ( $pv instanceof DataItem ) {
			$dv = DataValueFactory::getInstance()->newDataValueByItem( $pv, $prop );
			$dv->setOutputFormat( 'LOCL' );
			$text = $dv->getShortWikiText();
		}

		$this->htmlTable->cell( $text );

		// Instead of relying on the MDAT, use the File instance and check for
		// `File::isLocal`
		if ( $pv instanceof DataItem ) {
			$isLocal = '✓';
		} else {
			$isLocal = '✗';
		}

		$this->htmlTable->cell(
			$isLocal,
			[ 'style' => 'text-align:center' ]
		);

		$this->htmlTable->row();
	}

}
