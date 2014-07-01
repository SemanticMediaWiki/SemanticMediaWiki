<?php

namespace SMW\Tests\Util;

/**
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-sparql
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class FakeQueryResultProvider {

	/**
	 * @see https://www.w3.org/TR/rdf-sparql-query/#ask
	 */
	public function getEmptySparqlResultXml() {
		return '<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
			  <head>
			    <variable name="s"/>
			    <variable name="r"/>
			  </head>
			  <results>
			  </results>
			</sparql>';
	}

}
