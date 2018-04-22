<?php

namespace SMW\Rule;

use JsonContentHandler;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleContentHandler extends JsonContentHandler {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function __construct() {
		parent::__construct( CONTENT_MODEL_RULE, [ CONTENT_FORMAT_RULE ] );
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function getContentClass() {
		return RuleContent::class;
	}

}
