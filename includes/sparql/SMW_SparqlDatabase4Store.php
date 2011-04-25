<?php
/**
 * 4Store specific adjustments for SMWSparqlDatabase
 * 
 * @file
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */

/**
 * Specific modifications of the SPARQL database implementation for 4Store.
 * 
 * @ingroup SMWSparql
 * 
 * @author Markus Krötzsch
 */
class SMWSparqlDatabase4Store extends SMWSparqlDatabase {

	/**
	 * ASK wrapper.
	 * "ASK" is buggy in 4Store and must be avoided.
	 *
	 * @note This does not avoid the 4Store bug: all SPARQL queries are
	 * potentially answered incorrectly by 4Store v.1.1.2. Nothing we can
	 * do.
	 *
	 * @see SMWSparqlDatabase::ask
	 * @param $where string WHERE part of the query, without surrounding { }
	 * @param $extraNamespaces array (associative) of namespaceId => namespaceUri
	 * @return SMWSparqlResultWrapper
	 */
	public function ask( $where, $extraNamespaces = array() ) {
		$sparql = self::getPrefixString( $extraNamespaces ) . "SELECT * WHERE {\n" . $where . "\n} LIMIT 1";
		$result = $this->doQuery( $sparql );
		if ( $result->numRows() > 0 ) {
			$expLiteral = new SMWExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' );
		} else {
			$expLiteral = new SMWExpLiteral( 'false', 'http://www.w3.org/2001/XMLSchema#boolean' );
		}
		return new SMWSparqlResultWrapper( array( '' => 0 ), array( array( $expLiteral ) ) );
	}

}