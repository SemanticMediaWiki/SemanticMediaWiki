<?php

/**
 * Static class for hooks handled by the Semantic MediaWiki extension.
 *
 * @since 1.7
 *
 * @file SemanticMediaWiki.hooks.php
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
final class SMWHooks {

	/**
	 * TODO
	 *
	 * @since 1.7
	 *
	 * @return boolean
	 */
	public static function onPageSchemasRegistration() {
		// @codeCoverageIgnoreStart
		$GLOBALS['wgPageSchemasHandlerClasses'][] = 'SMWPageSchemas';

		return true;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Adds links to Admin Links page.
	 *
	 * @since 1.7
	 *
	 * @param ALTree $admin_links_tree
	 *
	 * @return boolean
	 */
	public static function addToAdminLinks( ALTree &$admin_links_tree ) {
		// @codeCoverageIgnoreStart
		$data_structure_section = new ALSection( wfMessage( 'smw_adminlinks_datastructure' )->text() );

		$smw_row = new ALRow( 'smw' );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Categories' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Properties' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'UnusedProperties' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'SemanticStatistics' ) );

		$data_structure_section->addRow( $smw_row );
		$smw_admin_row = new ALRow( 'smw_admin' );
		$smw_admin_row->addItem( ALItem::newFromSpecialPage( 'SMWAdmin' ) );

		$data_structure_section->addRow( $smw_admin_row );
		$smw_docu_row = new ALRow( 'smw_docu' );
		$smw_name = wfMessage( 'specialpages-group-smw_group' )->text();
		$smw_docu_label = wfMessage( 'adminlinks_documentation', $smw_name )->text();
		$smw_docu_row->addItem( AlItem::newFromExternalLink( 'http://semantic-mediawiki.org/wiki/Help:User_manual', $smw_docu_label ) );

		$data_structure_section->addRow( $smw_docu_row );
		$admin_links_tree->addSection( $data_structure_section, wfMessage( 'adminlinks_browsesearch' )->text() );
		$smw_row = new ALRow( 'smw' );
		$displaying_data_section = new ALSection( wfMessage( 'smw_adminlinks_displayingdata' )->text() );
		$smw_row->addItem( AlItem::newFromExternalLink(
			'http://semantic-mediawiki.org/wiki/Help:Inline_queries',
			wfMessage( 'smw_adminlinks_inlinequerieshelp' )->text()
		) );

		$displaying_data_section->addRow( $smw_row );
		$admin_links_tree->addSection( $displaying_data_section, wfMessage( 'adminlinks_browsesearch' )->text() );
		$browse_search_section = $admin_links_tree->getSection( wfMessage( 'adminlinks_browsesearch' )->text() );

		$smw_row = new ALRow( 'smw' );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Browse' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'Ask' ) );
		$smw_row->addItem( ALItem::newFromSpecialPage( 'SearchByProperty' ) );
		$browse_search_section->addRow( $smw_row );

		return true;
		// @codeCoverageIgnoreEnd
	}

}
