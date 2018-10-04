<?php

namespace SMW\MediaWiki\Search\Form;

use Html;
use WebRequest;
use SMW\Message;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortForm {

	/**
	 * @var WebRequest
	 */
	private $request;

	/**
	 * @var Field
	 */
	private $field;

	/**
	 * @var []
	 */
	private $parameters = [];

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 */
	public function __construct( WebRequest $request ) {
		$this->request = $request;
		$this->field = new Field();
	}

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @since 3.0
	 *
	 * @param array $features
	 */
	public function makeFields( $features = [] ) {

		$default = isset( $features['best'] ) && $features['best'] ? 'best' : 'title';
		$sort = $this->request->getVal( 'sort', $default );

		$this->parameters['sort'] = $sort;

		$list = [];
		$name = '';

		foreach ( $this->sortList( $features ) as $key => $value ) {

			if ( $key === $sort ) {
				$name = $value;
			}

			$list[] = [ 'id' => $key, 'name' => $value, 'desc' => $value ];
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-search-sort'
			],
			Html::rawElement(
				'button',
				[
					'type' => 'button',
					'id' => 'smw-search-sort',
					'class' => 'smw-selectmenu-button is-disabled',
					'name' => 'sort',
					'value' => $sort,
					'data-list' => json_encode( $list ),
					'title' => Message::get( 'smw-search-profile-extended-section-sort', Message::TEXT, Message::USER_LANGUAGE ),
				],
				$sort === '' ? 'Sort' : $name
			) . Html::rawElement(
				'input',
				[
					'type' => 'hidden',
					'name' => 'sort',
					'value' => $sort,
				]
			)
		);
	}

	private function sortList( $features ) {

		$list = [];

		if ( isset( $features['best'] ) && $features['best'] ) {
			$list['best'] = Message::get( 'smw-search-profile-sort-best', Message::TEXT, Message::USER_LANGUAGE );

			$list += [
				'recent' => Message::get( 'smw-search-profile-sort-recent', Message::TEXT, Message::USER_LANGUAGE ),
				'title'  => Message::get( 'smw-search-profile-sort-title', Message::TEXT, Message::USER_LANGUAGE )
			];

		} else{
			$list = [
				'title'  => Message::get( 'smw-search-profile-sort-title', Message::TEXT, Message::USER_LANGUAGE ),
				'recent' => Message::get( 'smw-search-profile-sort-recent', Message::TEXT, Message::USER_LANGUAGE )
			];
		}

		return $list;
	}

}
