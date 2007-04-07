<?php
/**
 * @author Daniel M. Herzig
 * @version 0.1
 *
 * This special page of the Semantic Media Wiki Extension displays some statistics about relations and attributes.
 */
if (!defined('MEDIAWIKI')) die();

global $IP, $smwgIP;

require_once( "$IP/includes/SpecialPage.php" );
require_once( "$IP/includes/Title.php" );

SpecialPage::addPage( new ExtendedStatistics );


class ExtendedStatistics extends SpecialPage {

	function ExtendedStatistics() {
		SpecialPage::SpecialPage( 'ExtendedStatistics' );
		$this->includable( true );
	}

	function getName() {
		return "extendedstatistics";
	}

	function execute( $par = null ) {
		global $wgOut, $wgLang;

		$dbr =& wfGetDB( DB_SLAVE );

		$views = SiteStats::views();
		$edits = SiteStats::edits();
		$good = SiteStats::articles();
		$images = SiteStats::images();
		$users = SiteStats::users();


		$relations_table = $dbr->tableName( 'smw_relations' );
		$attributes_table = $dbr->tableName( 'smw_attributes' );
		$page_table = $dbr->tableName( 'page' );


		$sql = "SELECT Count(DISTINCT relation_title) AS count FROM $relations_table";
		$res = $dbr->query( $sql );
		$row = $dbr->fetchObject( $res );
		$relations = $wgLang->formatNum($row->count);
		$dbr->freeResult( $res );

		$sql = "SELECT Count(*) AS count FROM $relations_table";
		$res = $dbr->query( $sql );
		$row = $dbr->fetchObject( $res );
		$relation_instance = $wgLang->formatNum($row->count);
		$dbr->freeResult( $res );

		$sql  = "SELECT Count(*) AS count ";
		$sql .= "FROM $page_table ";
		$sql .= "where page_title IN ";
		$sql .= "(SELECT DISTINCT $relations_table.relation_title FROM $relations_table);";
		$res = $dbr->query( $sql );
		$row = $dbr->fetchObject( $res );
		$relation_pages = $wgLang->formatNum($row->count);
		$dbr->freeResult( $res );

		$sql = "SELECT Count(DISTINCT attribute_title) AS count FROM $attributes_table";
		$res = $dbr->query( $sql );
		$row = $dbr->fetchObject( $res );
		$attributes = $wgLang->formatNum($row->count);
		$dbr->freeResult( $res );

		$sql = "SELECT Count(*) AS count FROM $attributes_table";
		$res = $dbr->query( $sql );
		$row = $dbr->fetchObject( $res );
		$attribute_instance = $wgLang->formatNum($row->count);
		$dbr->freeResult( $res );



		$out = "<table >
				<tr>
					<td><h2>" . wfMsg('smw_extstats_general') ."</h2></td>
					<td></td>
				</tr>
				<tr>
						<td>" . wfMsg('smw_extstats_totalp') ."</td>
						<td>".$good."</td>
				</tr>
				<tr>
						<td>" . wfMsg('smw_extstats_totalv') ."</td>
						<td>".$views."</td>
				</tr>
				<tr>
						<td>" . wfMsg('smw_extstats_totalpe') ."</td>
						<td>".$edits."</td>
				</tr>
				<tr>
						<td>" . wfMsg('smw_extstats_totali') ."</td>
						<td>".$images."</td>
				</tr>
				<tr>
						<td>" . wfMsg('smw_extstats_totalu') ."</td>
						<td>".$users." </td>
				</tr>
				</table>";

		$out .= "<table>
					<tr>
							<td><h2>" . wfMsg('extendedstatistics') ."</h2> </td>
							<td> </td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totalr') ."</td>
							<td>".$relations." </td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totalri') ."</td>
							<td>".$relation_instance."</td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totalra') ."</td>
							<td>".$wgLang->formatNum( sprintf( '%0.2f', $relation_instance!=0 ? $relation_instance / $relations : 0 ) ) ."</td>
					</tr>

					<tr>
							<td>" . wfMsg('smw_extstats_totalpr') ."</td>
							<td>".$relation_pages." </td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totala') ."</td>
							<td>".$attributes."</td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totalai') ."</td>
							<td>".$attribute_instance."</td>
					</tr>
					<tr>
							<td>" . wfMsg('smw_extstats_totalaa') ."</td>
							<td>".$wgLang->formatNum( sprintf( '%0.2f', $attribute_instance!=0 ? $attribute_instance / $attributes : 0 ) ) ." </td>
					</tr>
				</table>";

		$wgOut->addHTML( $out );

	}
}

?>
