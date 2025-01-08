<?php

namespace SMW\Importer;

use Onoi\MessageReporter\MessageReporterAware;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
interface ContentCreator extends MessageReporterAware {

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 *
	 * @return bool
	 */
	public function canCreateContentsFor( ImportContents $importContents );

	/**
	 * @since 3.0
	 *
	 * @param ImportContents $importContents
	 */
	public function create( ImportContents $importContents );

}
