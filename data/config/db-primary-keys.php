<?php

use MediaWiki\MediaWikiServices;
use Onoi\MessageReporter\MessageReporter;
use SMW\Utils\CliMsgFormatter;

/**
 * This profile adds primary keys to Semantic MediaWiki owned tables to help DB
 * environments like `Percona XtraDB Cluster` (#4724, #4507) that require those
 * keys.
 *
 * Beware that while the profile is provided as part of the Semantic MediaWiki
 * software it does NOT imply that systems like `Percona` are officially
 * supported as there is no test setup that runs the required test suite.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Config_preloading
 *
 * @since 3.2
 */

class ConfigPreloadPrimaryKeyTableMutator {

	// #3559
	const PRIMARY_KEYS = [

		// Common property value tables
		'smw_di_blob'     => 's_id,p_id,o_hash',
		'smw_di_bool'     => 's_id,p_id,o_value',
		'smw_di_coords'   => 's_id,p_id,o_serialized',
		'smw_di_number'   => 's_id,p_id,o_serialized',
		'smw_di_time'     => 's_id,p_id,o_serialized',
		'smw_di_uri'      => 's_id,p_id,o_serialized',
		'smw_di_wikipage' => 's_id,p_id,o_id',

		// Fixed property value tables
		'smw_fpt_ask'     => 's_id,o_id',
		'smw_fpt_askde'   => 's_id,o_serialized',
		'smw_fpt_askfo'   => 's_id,o_hash',
		'smw_fpt_askdu'   => 's_id,o_serialized',
		'smw_fpt_asksi'   => 's_id,o_serialized',
		'smw_fpt_askst'   => 's_id,o_hash',
		'smw_fpt_askpa'   => 's_id,o_hash',
		'smw_fpt_cdat'    => 's_id,o_serialized',
		'smw_fpt_conc'    => 's_id',
		'smw_fpt_conv'    => 's_id,o_hash',
		'smw_fpt_dtitle'  => 's_id,o_hash',
		'smw_fpt_impo'    => 's_id,o_hash',
		'smw_fpt_inst'    => 's_id,o_id',
		'smw_fpt_lcode'   => 's_id,o_hash',
		'smw_fpt_ledt'    => 's_id,o_id',
		'smw_fpt_list'    => 's_id,o_hash',
		'smw_fpt_mdat'    => 's_id,o_serialized',
		'smw_fpt_media'   => 's_id,o_hash',
		'smw_fpt_mime'    => 's_id,o_hash',
		'smw_fpt_newp'    => 's_id,o_value',
		'smw_fpt_pplb'    => 's_id,o_id',
		'smw_fpt_prec'    => 's_id,o_serialized',
		'smw_fpt_pval'    => 's_id,o_hash',
		'smw_fpt_redi'    => 's_title,s_namespace',
		'smw_fpt_serv'    => 's_id,o_hash',
		'smw_fpt_sobj'    => 's_id,o_id',
		'smw_fpt_subc'    => 's_id,o_id',
		'smw_fpt_subp'    => 's_id,o_id',
		'smw_fpt_text'    => 's_id,o_hash',
		'smw_fpt_type'    => 's_id,o_serialized',
		'smw_fpt_unit'    => 's_id,o_hash',
		'smw_fpt_uri'     => 's_id,o_serialized',

		// Other data tables
		'smw_object_ids'  => 'smw_id',
		'smw_object_aux'  => 'smw_id',
		'smw_prop_stats'  => 'p_id',
		'smw_query_links' => 's_id,o_id',
		'smw_ft_search'   => 's_id,p_id',
		'smw_concept_cache' => 's_id,o_id'
	];

	/**
	 * @param string $tableName
	 */
	public function hasKey( string $tableName ) : bool {
		return self::PRIMARY_KEYS[$tableName] ?? false;
	}

	/**
	 * @param string $tableName
	 */
	public function getKey( string $tableName ) : string {
		return self::PRIMARY_KEYS[$tableName];
	}
}

/**
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.sqlstore.installer.beforecreatetablescomplete.md
 */
MediaWikiServices::getInstance()->getHookContainer()->register( 'SMW::SQLStore::Installer::BeforeCreateTablesComplete', function(array $tables, MessageReporter $messageReporter ) {

	$cliMsgFormatter = new CliMsgFormatter();
	$configPreloadPrimaryKeyTableMutator = new ConfigPreloadPrimaryKeyTableMutator();

	$messageReporter->reportMessage(
		$cliMsgFormatter->section( 'Primary key(s)', 3, '-', true )
	);

	$i = 0;

	$text = [
		'The following updates adds primary key information for the tables',
		'owned by Semantic MediaWiki.'
	];

	$messageReporter->reportMessage(
		"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
	);

	$messageReporter->reportMessage(
		"\n" . $cliMsgFormatter->oneCol( "Checking table definitions ..." )
	);

	/**
	 * @var \SMW\SQLStore\TableBuilder\Table[]
	 */
	foreach ( $tables as $table ) {

		$tableName = $table->getName();

		if ( !$configPreloadPrimaryKeyTableMutator->hasKey( $tableName ) ) {
			continue;
		}

		$i++;

		$table->setPrimaryKey(
			$configPreloadPrimaryKeyTableMutator->getKey( $tableName )
		);
	}

	$messageReporter->reportMessage(
		$cliMsgFormatter->twoCols( "... run table definition update ...", "$i (tables)", 3 )
	);

	$messageReporter->reportMessage(
		$cliMsgFormatter->oneCol( "... done.", 3 )
	);

} );

return [

	// Modify the upgrade key to make sure an update is forced in the event this
	// profile is used (or removed).
	'smwgUpgradeKey' => $GLOBALS['smwgUpgradeKey'] . ':primary'
];
