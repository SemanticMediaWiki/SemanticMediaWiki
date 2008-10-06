<?php
/**
 * @author Markus Krötzsch
 *
 * An abstract query page base class that supports array-based
 * data retrieval instead of the SQL-based access used by MW.
 * @file
 * @ingroup SMW
 */

/**
 * Abstract base class for SMW's variant of the MW QueryPage.
 * Subclasses must implement getResults() and formatResult(), as
 * well as some other standard functions of QueryPage.
 * @ingroup SMW
 */
abstract class SMWQueryPage extends QueryPage {

	/**
	 * Implemented by subclasses to provide concrete functions.
	 */
	abstract function getResults($requestoptions);

	/**
	 * Clear the cache and save new results
	 */
	function recache( $limit, $ignoreErrors = true ) {
		///TODO
	}

	/**
	 * This is the actual workhorse. It does everything needed to make a
	 * real, honest-to-gosh query page.
	 * Alas, we need to overwrite the whole beast since we do not assume
	 * an SQL-based storage backend.
	 *
	 * @param $offset database query offset
	 * @param $limit database query limit
	 * @param $shownavigation show navigation like "next 200"?
	 */
	function doQuery( $offset, $limit, $shownavigation=true ) {
		global $wgUser, $wgOut, $wgLang, $wgContLang;

		$options = new SMWRequestOptions();
		$options->limit = $limit;
		$options->offset = $offset;
		$options->sort = true;
		$res = $this->getResults($options);
		$num = count($res);

		$sk = $wgUser->getSkin();
		$sname = $this->getName();

		if($shownavigation) {
			$wgOut->addHTML( $this->getPageHeader() );

			// if list is empty, show it
			if( $num == 0 ) {
				wfLoadExtensionMessages('SemanticMediaWiki');
				$wgOut->addHTML( '<p>' . wfMsgHTML('specialpage-empty') . '</p>' );
				return;
			}

			$top = wfShowingResults( $offset, $num);
			$wgOut->addHTML( "<p>{$top}\n" );

			// often disable 'next' link when we reach the end
			$atend = $num < $limit;

			$sl = wfViewPrevNext( $offset, $limit ,
				$wgContLang->specialPage( $sname ),
				wfArrayToCGI( $this->linkParameters() ), $atend );
			$wgOut->addHTML( "<br />{$sl}</p>\n" );
		}
		if ( $num > 0 ) {
			$s = array();
			if ( ! $this->listoutput )
				$s[] = $this->openList( $offset );

			foreach ($res as $r) {
				$format = $this->formatResult( $sk, $r );
				if ( $format ) {
					$s[] = $this->listoutput ? $format : "<li>{$format}</li>\n";
				}
			}

			if ( ! $this->listoutput )
				$s[] = $this->closeList();
			$str = $this->listoutput ? $wgContLang->listToText( $s ) : implode( '', $s );
			$wgOut->addHTML( $str );
		}
		if($shownavigation) {
			$wgOut->addHTML( "<p>{$sl}</p>\n" );
		}
		return $num;
	}

}

