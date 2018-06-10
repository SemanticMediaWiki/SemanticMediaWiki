<?php

namespace SMW\MediaWiki\Search\Form;

use Html;
use WebRequest;

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

		$sort = $this->request->getVal( 'sort' );
		$this->parameters['sort'] = $sort;

		$attributes = [
			'id'   => 'sort',
			'class' => '',
			'list' => $this->sortList( $features ),
			'name' => 'sort',
			'selected' => $sort,
			'label' => 'Sort by:&nbsp;'
		];

		$select = $this->field->create( 'select', $attributes );

		return Html::rawElement(
			'div',
			[
				'id'    => 'smw-search-sort',
				'class' => 'smw-select',
				'style' => 'margin-right:10px;'
			],
			$select
		);
	}

	private function sortList( $features ) {

		$list = [];

		if ( isset( $features['best'] ) && $features['best'] ) {
			$list['best'] = 'Best Match';

			$list += [
				'recent' => 'Most Recent',
				'title'  => 'Title'
			];

		} else{
			$list = [
				'title'  => 'Title',
				'recent' => 'Most Recent'
			];
		}

		return $list;
	}

}
