<?php

/**
 * Static class for hooks handled by the SMW extension.
 * 
 * This class is an attempt to clean up SMW initialization, as it's rather messy at the moment,
 * and the split between SMW and SMW light is not very clean.
 * 
 * @since 1.5.3
 * 
 * @file SMW.hooks.php
 * @ingroup SMW
 * 
 * @author Jeroen De Dauw
 */
final class SMWHooks {
	
	/**
	 * Register the resource modules for the resource loader.
	 * 
	 * @since 1.5.3
	 * 
	 * @param ResourceLoader $resourceLoader
	 * 
	 * @return true
	 */
	public static function registerResourceLoaderModules( ResourceLoader &$resourceLoader ) {
		global $wgContLang, $smwgScriptPath;

		$modules = array(
			'ext.smw.style' => array(
				'styles' => 'SMW_custom.css'
			),
			'ext.smw.tooltips' => array(
				'scripts' => 'SMW_tooltip.js',
				'dependencies' => array(
					'mediawiki.legacy.wikibits',
					'ext.smw.style'
				)
			),
			'ext.smw.sorttable' => array(
				'scripts' => 'SMW_sorttable.js',
				'dependencies' => 'ext.smw.style'
			)		
		);
		
		foreach ( $modules as $name => $resources ) {
			$resourceLoader->register(
				$name,
				new ResourceLoaderFileModule(
					array_merge_recursive( $resources, array( 'group' => 'ext.smw' ) ),
					dirname( __FILE__ ) . '/skins',
					$smwgScriptPath . '/skins'
				)
			); 
		}
		
		return true;
	}	
	
}