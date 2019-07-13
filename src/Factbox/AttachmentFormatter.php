<?php

namespace SMW\Factbox;

use SMW\Store;
use SMW\DataValueFactory;
use SMW\PropertyRegistry;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\Utils\HtmlDivTable;
use SMWDIBlob as DIBlob;
use SMWDataItem as DataItem;
use Html;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AttachmentFormatter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $header = '';

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function setHeader( $header ) {
		$this->header = $header;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function buildHTML( array $attachments = [] ) {

		if ( $attachments === [] ) {
			return '';
		}

		$dataValueFactory = DataValueFactory::getInstance();

		$html = '';
		$rows = '';

		$property = new DIProperty( '_ATTCH_LINK' );

		foreach ( $attachments as $dataItem ) {
			$rows .= HtmlDivTable::row( $this->buildRow( $property, $dataItem ) );
		}

		$propertyRegistry = PropertyRegistry::getInstance();

		$mime = $dataValueFactory->newDataValueByItem(
			 ( new DIProperty( '_MIME' ) )->getDiWikiPage()
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
			( new DIProperty( '_MDAT' )  )->getDiWikiPage()
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

		$html .= Html::rawElement(
			'div',
			[
				'class' => 'smwfact',
				'style' => 'display:block;'
			],
			$this->header . HtmlDivTable::table(
				HtmlDivTable::header(
					HtmlDivTable::cell( '&nbsp;', [ 'style' => 'width:50%;'] ) .
					HtmlDivTable::cell( $mime->getShortWikiText(), [ 'style' => 'width:20%;'] ) .
					HtmlDivTable::cell( $mdat->getShortWikiText(), [ 'style' => 'width:20%;'] ) .
					HtmlDivTable::cell( $isLocalMsg, [ 'style' => 'width:10%;'] )
				) . HtmlDivTable::body( $rows ),
				[
					// ID is used for the sorting JS!
					'id'    => 'smw-factbox-attachments',
					'class' => 'smwfacttable'
				]
			)
		);

		return $html;
	}

	private function buildRow( $property, $dataItem ) {

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

		$row = HtmlDivTable::cell( $attachment );

		$pv = $this->store->getPropertyValues( $dataItem, new DIProperty( '_MIME' ) );
		$pv = is_array( $pv ) ? end( $pv ) : '';

		$row .= HtmlDivTable::cell(
			$pv instanceof DIBlob ? $pv->getString() : $unknown,
			[ 'style' => 'word-break: break-word;' ]
		);

		$prop = new DIProperty( '_MDAT' );
		$text = $unknown;

		$pv = $this->store->getPropertyValues( $dataItem, $prop );
		$pv = is_array( $pv ) ? end( $pv ) : '';

		if ( $pv instanceof DataItem ) {
			$dv = DataValueFactory::getInstance()->newDataValueByItem( $pv, $prop );
			$dv->setOutputFormat( 'LOCL' );
			$text = $dv->getShortWikiText();
		}

		$row .= HtmlDivTable::cell( $text );

		// Instead of relying on the MDAT, use the File instance and check for
		// `File::isLocal`
		if ( $pv instanceof DataItem ) {
			$isLocal = '✓';
		} else {
			$isLocal = '✗';
		}

		$row .= HtmlDivTable::cell( $isLocal );

		return $row;
	}

}
