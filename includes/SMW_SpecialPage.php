<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

/**
 * A simple extension of SpecialPage that ensures that all relevant SMW-user
 * messages are loaded when the special page is initialised. This is especially
 * relevant as an adaptor for query pages.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWSpecialPage extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct($name, $function, $file = 'default', $listed=true, $restriction='', $group='' /*depreciated*/) {
		wfLoadExtensionMessages('SemanticMediaWiki');
		parent::__construct($name, $restriction, $listed, $function, $file);
	}

}
