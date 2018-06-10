<?php

namespace SMW\Rule;

use JsonContent;
use Status;
use Title;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleContent extends JsonContent {

	/**
	 * @see JsonContent
	 */
	public function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_RULE );
	}

}
