<?php
/**
 * @deprecated This obsolete file will soon vanish.
 * @file
 * @ingroup SMWDataValues
 */

/**
 * This datavalue is similiar to SMWWikiPageValue in that it represents pages
 * in the wiki. However, it is tailored for uses where it is enough to store
 * the title string of the page without namespace, interwiki prefix, or
 * sortkey. This is useful for "special" properties like "Has type" where the
 * namespace is fixed, and which do not need any of the other settings. The
 * advantage of the reduction of data is that these important values can be
 * stored in smaller tables that allow for faster direct access than general
 * page type values.
 * 
 * @deprecated This auxiliary class will vanish in SMW 1.6
 *
 * @author Markus Krötzsch
 * @ingroup SMWDataValues
 */
class SMWSimpleWikiPageValue extends SMWWikiPageValue {

}