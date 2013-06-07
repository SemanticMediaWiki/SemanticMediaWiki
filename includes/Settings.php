<?php

namespace SMW;

use ArrayObject;

/**
 * Encapsulate settings (such as $GLOBALS) in an instantiatable settings class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames (Initial idea has been "stolen" from Jeroen De Dauw)
 */

/**
 * This class encapsulates settings (such as $GLOBALS) in order to make it
 * an instantiatable object
 *
 * @ingroup SMW
 */
class Settings {

	/**
	 * Defines settings as static to ensure it is only
	 * instantiated once per session (e.g. SMWUpdateJobs etc.)
	 * @var ArrayObject
	 */
	protected $settings;

	/** @var Settings */
	private static $instance = null;

	/**
	 * @note Use composition over inheritance, if it necessary this class can
	 * extended to use the ArrayObject without interrupting the interface
	 * therefore use the factory methods for instantiation
	 *
	 * @since 1.9
	 *
	 * @param ArrayObject $settings
	 */
	protected function __construct( ArrayObject $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Assemble individual smwg* settings into one accessible array
	 * for easy instantiation
	 *
	 * Since we don't have unique way of accessing only SMW related settings (
	 * e.g. $smwgSettings['...']) we need this methods as short cut to
	 * invoke only smwg* related settings
	 *
	 * @par Example:
	 * @code
	 *  $defaultStore = \SMW\Settings::newFromGlobals()->get( 'smwgDefaultStore' );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromGlobals() {

		$settings = array(
			'smwgScriptPath' => $GLOBALS['smwgScriptPath'],
			'smwgIP' => $GLOBALS['smwgIP'],
			'smwgDefaultStore' => $GLOBALS['smwgDefaultStore'],
			'smwgSparqlDatabase' => $GLOBALS['smwgSparqlDatabase'],
			'smwgSparqlQueryEndpoint' => $GLOBALS['smwgSparqlQueryEndpoint'],
			'smwgSparqlUpdateEndpoint' => $GLOBALS['smwgSparqlUpdateEndpoint'],
			'smwgSparqlDataEndpoint' => $GLOBALS['smwgSparqlDataEndpoint'],
			'smwgSparqlDefaultGraph' => $GLOBALS['smwgSparqlDefaultGraph'],
			'smwgHistoricTypeNamespace' => $GLOBALS['smwgHistoricTypeNamespace'],
			'smwgNamespaceIndex' => $GLOBALS['smwgNamespaceIndex'],
			'smwgShowFactbox' => $GLOBALS['smwgShowFactbox'],
			'smwgShowFactboxEdit' => $GLOBALS['smwgShowFactboxEdit'],
			'smwgToolboxBrowseLink' => $GLOBALS['smwgToolboxBrowseLink'],
			'smwgInlineErrors' => $GLOBALS['smwgInlineErrors'],
			'smwgUseCategoryHierarchy' => $GLOBALS['smwgUseCategoryHierarchy'],
			'smwgCategoriesAsInstances' => $GLOBALS['smwgCategoriesAsInstances'],
			'smwgLinksInValues' => $GLOBALS['smwgLinksInValues'],
			'smwgDefaultNumRecurringEvents' => $GLOBALS['smwgDefaultNumRecurringEvents'],
			'smwgMaxNumRecurringEvents' => $GLOBALS['smwgMaxNumRecurringEvents'],
			'smwgBrowseShowInverse' => $GLOBALS['smwgBrowseShowInverse'],
			'smwgBrowseShowAll' => $GLOBALS['smwgBrowseShowAll'],
			'smwgSearchByPropertyFuzzy' => $GLOBALS['smwgSearchByPropertyFuzzy'],
			'smwgTypePagingLimit' => $GLOBALS['smwgTypePagingLimit'],
			'smwgConceptPagingLimit' => $GLOBALS['smwgConceptPagingLimit'],
			'smwgPropertyPagingLimit' => $GLOBALS['smwgPropertyPagingLimit'],
			'smwgQEnabled' => $GLOBALS['smwgQEnabled'],
			'smwgQMaxLimit' => $GLOBALS['smwgQMaxLimit'],
			'smwgIgnoreQueryErrors' => $GLOBALS['smwgIgnoreQueryErrors'],
			'smwgQSubcategoryDepth' => $GLOBALS['smwgQSubcategoryDepth'],
			'smwgQEqualitySupport' => $GLOBALS['smwgQEqualitySupport'],
			'smwgQSortingSupport' => $GLOBALS['smwgQSortingSupport'],
			'smwgQRandSortingSupport' => $GLOBALS['smwgQRandSortingSupport'],
			'smwgQDefaultNamespaces' => $GLOBALS['smwgQDefaultNamespaces'],
			'smwgQComparators' => $GLOBALS['smwgQComparators'],
			'smwStrictComparators' => $GLOBALS['smwStrictComparators'],
			'smwgQMaxSize' => $GLOBALS['smwgQMaxSize'],
			'smwgQMaxDepth' => $GLOBALS['smwgQMaxDepth'],
			'smwgQFeatures' => $GLOBALS['smwgQFeatures'],
			'smwgQDefaultLimit' => $GLOBALS['smwgQDefaultLimit'],
			'smwgQMaxInlineLimit' => $GLOBALS['smwgQMaxInlineLimit'],
			'smwgQPrintoutLimit' => $GLOBALS['smwgQPrintoutLimit'],
			'smwgQDefaultLinking' => $GLOBALS['smwgQDefaultLinking'],
			'smwgQConceptCaching' => $GLOBALS['smwgQConceptCaching'],
			'smwgQConceptMaxSize' => $GLOBALS['smwgQConceptMaxSize'],
			'smwgQConceptMaxDepth' => $GLOBALS['smwgQConceptMaxDepth'],
			'smwgQConceptFeatures' => $GLOBALS['smwgQConceptFeatures'],
			'smwgQConceptCacheLifetime' => $GLOBALS['smwgQConceptCacheLifetime'],
			'smwgResultFormats' => $GLOBALS['smwgResultFormats'],
			'smwgResultAliases' => $GLOBALS['smwgResultAliases'],
			'smwgQuerySources' => $GLOBALS['smwgQuerySources'],
			'smwgPDefaultType' => $GLOBALS['smwgPDefaultType'],
			'smwgAllowRecursiveExport' => $GLOBALS['smwgAllowRecursiveExport'],
			'smwgExportBacklinks' => $GLOBALS['smwgExportBacklinks'],
			'smwgMaxNonExpNumber' => $GLOBALS['smwgMaxNonExpNumber'],
			'smwgEnableUpdateJobs' => $GLOBALS['smwgEnableUpdateJobs'],
			'smwgNamespacesWithSemanticLinks' => $GLOBALS['smwgNamespacesWithSemanticLinks'],
			'smwgPageSpecialProperties' => $GLOBALS['smwgPageSpecialProperties'],
			'smwgDeclarationProperties' => $GLOBALS['smwgDeclarationProperties'],
			'smwgTranslate' => $GLOBALS['smwgTranslate'],
			'smwgAdminRefreshStore' => $GLOBALS['smwgAdminRefreshStore'],
			'smwgAutocompleteInSpecialAsk' => $GLOBALS['smwgAutocompleteInSpecialAsk'],
			'smwgAutoRefreshSubject' => $GLOBALS['smwgAutoRefreshSubject'],
			'smwgCacheType' => $GLOBALS['smwgCacheType'],
			'smwgAutoRefreshOnPurge' => $GLOBALS['smwgAutoRefreshOnPurge'],
			'smwgAutoRefreshOnPageMove' => $GLOBALS['smwgAutoRefreshOnPageMove'],
			'smwgContLang' => $GLOBALS['smwgContLang'],
			'smwgMaxPropertyValues' => $GLOBALS['smwgMaxPropertyValues'],
			'smwgQSubpropertyDepth' => $GLOBALS['smwgQSubpropertyDepth'],
			'smwgNamespace' => $GLOBALS['smwgNamespace'],
			'smwgMasterStore' => $GLOBALS['smwgMasterStore'],
			'smwgIQRunningNumber' => $GLOBALS['smwgIQRunningNumber'],
		);

		if ( self::$instance === null ) {
			self::$instance = self::newFromArray( $settings ) ;
		}

		return self::$instance;
	}

	/**
	 * Factory methods to instantiation a Settings object from a normal
	 * array
	 *
	 * @par Example:
	 * @code
	 *  $settings = \SMW\Settings::newFromArray( array() );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public static function newFromArray( array $settings ) {
		return new self( new ArrayObject( $settings ) );
	}

	/**
	 * Returns if the specified settings is set or not
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function exists( $key ) {
		return $this->settings->offsetExists( $key );
	}

	/**
	 * Overrides settings for a specific key in a class context
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ) {
		$this->settings->offsetSet( $key, $value );
		return $this;
	}

	/**
	 * Returns settings for a specific key
	 *
	 * @since 1.9
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if ( !$this->exists( $key ) ) {
			throw new SettingsArgumentException( "{$key} is not a valid settings key" );
		}
		return $this->settings->offsetGet( $key );
	}

	/**
	 * Reset instance
	 *
	 * @since 1.9
	 */
	public static function reset() {
		self::$instance = null;
	}
}
