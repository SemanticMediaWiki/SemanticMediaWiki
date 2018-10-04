<?php

namespace SMW\MediaWiki\Search\Form;

use SMW\DIProperty;
use SMW\MediaWiki\Search\SearchProfileForm;
use SMW\RequestOptions;
use SMW\Store;
use SMWDIBlob as DIBlob;
use Title;
use WikiPage;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getFormDefinitions() {

		$data = [];
		$requestOptions = new RequestOptions();
		$requestOptions->setOption( 'DISTINCT', false );

		$subjects = $this->store->getPropertySubjects(
			new DIProperty( '_SCHEMA_TYPE' ),
			new DIBlob( SearchProfileForm::SCHEMA_TYPE ),
			$requestOptions
		);

		foreach ( $subjects as $subject ) {

			if ( ( $nativeData = $this->getNativeData( $subject->getTitle() ) ) === '' ) {
				continue;
			}

			$d = json_decode( $nativeData, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			$data = array_merge_recursive( $data, $d );
		}

		return $data;
	}

	protected function getNativeData( $title ) {

		if ( $title === null ) {
			return '';
		}

		$content = WikiPage::factory( $title )->getContent();

		if ( $content === null ) {
			return '';
		}

		return $content->getNativeData();
	}

}
