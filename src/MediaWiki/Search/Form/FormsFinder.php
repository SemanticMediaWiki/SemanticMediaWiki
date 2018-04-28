<?php

namespace SMW\MediaWiki\Search\Form;

use SMW\MediaWiki\Search\SearchProfileForm;
use SMW\Store;
use SMW\RequestOptions;
use SMWDIBlob as DIBlob;
use SMW\DIProperty;
use WikiPage;
use Title;

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
			new DIProperty( '_RL_TYPE' ),
			new DIBlob( SearchProfileForm::RULE_TYPE ),
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

		$content = WikiPage::factory( $title )->getContent();

		if ( $content === null ) {
			return '';
		}

		return $content->getNativeData();
	}

}
