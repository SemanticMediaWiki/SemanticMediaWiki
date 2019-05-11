<?php

namespace SMW\MediaWiki\Search\ProfileForm;

use SMW\MediaWiki\Search\ProfileForm\Forms\OpenForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\CustomForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\SortForm;
use SMW\MediaWiki\Search\ProfileForm\Forms\NamespaceForm;
use WebRequest;
use SMW\ApplicationFactory;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormsFactory {

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 *
	 * @return OpenForm
	 */
	public function newOpenForm( WebRequest $request ) {
		return new OpenForm( $request );
	}

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 *
	 * @return CustomForm
	 */
	public function newCustomForm( WebRequest $request ) {
		return new CustomForm( $request );
	}

	/**
	 * @since 3.0
	 *
	 * @param WebRequest $request
	 *
	 * @return SortForm
	 */
	public function newSortForm( WebRequest $request ) {
		return new SortForm( $request );
	}

	/**
	 * @since 3.0
	 *
	 * @return NamespaceForm
	 */
	public function newNamespaceForm() {
		return new NamespaceForm(
			ApplicationFactory::getInstance()->singleton( 'NamespaceInfo' )
		);
	}

}
