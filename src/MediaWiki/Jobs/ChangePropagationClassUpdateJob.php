<?php

namespace SMW\MediaWiki\Jobs;

use Title;

/**
 * Isolate instance to count update jobs in connection with a category related
 * update.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ChangePropagationClassUpdateJob extends ChangePropagationUpdateJob {

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {

		$params = $params + [
			'origin' => 'ChangePropagationClassUpdateJob'
		];

		parent::__construct( $title, $params, 'smw.changePropagationClassUpdate' );
	}

}
