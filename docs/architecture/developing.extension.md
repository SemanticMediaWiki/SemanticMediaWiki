 [Extensions](https://github.com/SemanticMediaWiki/) maintained by the project try follow some simple guidelines, also to make maintenance easier with a common infrastructure and setup:

- Use [`Composer`](https://packagist.org/packages/mediawiki/) as deployment tool
- Loaded via MediaWiki's `extension.json`
- Use Travis-CI as continuous integration platform

##  Guidelines and conventions

Some general guidelines are:

- An extension specifies its dependency to ensure it is tested and usable for the Semantic MediaWiki release it was intended for by maintaining a `composer.json`
- Use `PSR-4` and a [PHP namespace](http://php.net/manual/en/language.namespaces.php) to distinguish a codebase from other repositories that may use similar (or even the same) class/interface names.
- Code quality should be considered when writing an extension in order for fellow developers to be able to understand, read, and review the code.
- See also the [coding conventions](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/architecture/coding.conventions.md) defined by Semantic MediaWiki
- Deploy and write tests as part of the extension

## Getting started ...

The following extensions can be used as inspiration on "How to write an extension" in connection with Semantic MediaWiki.

- [`SemanticMetaTags`](https://github.com/SemanticMediaWiki/SemanticMetaTags)
- [`SemanticApprovedRevs `](https://github.com/SemanticMediaWiki/SemanticApprovedRevs)
- [`SemanticExtraSpecialProperties `](https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties)
- [`SemanticBreadcrumbLinks `](https://github.com/SemanticMediaWiki/SemanticBreadcrumbLinks)
- [`SemanticScribunto `](https://github.com/SemanticMediaWiki/SemanticScribunto)
- [`SemanticGlossary  `](https://github.com/SemanticMediaWiki/SemanticGlossary)

## Technical notes

### Files and folders

It has been good practice to code around the following files and folders structure:

<pre>
docs/
i18n/
src/
tests/
</pre>

### Continuous integration

Using Travis-CI is fairly easy to setup and integrable with Semantic MediaWiki, the best approach is to select an existing repository and copy files such as:

- [`.travis.yml`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs/blob/master/.travis.yml)
- [`tests/travis`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs/tree/master/tests/travis) folder and adapt the necessary references
- When you host your extension with GitHub remember to register your Travis-CI either as App or via the webhook (see the documentation from the provider)
- [`phpunit.xml.dist`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs/blob/master/phpunit.xml.dist) and the [`tests/bootstrap.php`](https://github.com/SemanticMediaWiki/SemanticApprovedRevs/blob/master/tests/bootstrap.php) and make necessary changes

### composer.json

<pre>
	"require": {
		"php": ">=5.6.0",
		"composer/installers": "1.*,>=1.0.1",
		"mediawiki/semantic-media-wiki": "~3.0"
	},
	"extra": {
		"branch-alias": {
			"dev-master": "0.1.x-dev"
		}
	},
	"autoload": {
		"files" : [
			"Foo.php"
		],
		"psr-4": {
			"Foo\\": "src/"
		}
	}
</pre>

### extension.json

<pre>
{
	"name": "Foo",
	"version": "0.1-alpha",
	"author": [
		"Foo",
		"..."
	],
	"descriptionmsg": "foo-desc",
	"namemsg": "foo-name",
	"license-name": "GPL-2.0-or-later",
	"type": "foo",
	"requires": {
		"MediaWiki": ">= 1.30"
	},
	"MessagesDirs": {
		"Foo": [
			"i18n"
		]
	},
	"callback": "Foo::initExtension",
	"ExtensionFunctions": [
		"Foo::onExtensionFunction"
	],
	"load_composer_autoloader":true,
	"manifest_version": 1
}
</pre>

### Foo.php

<pre>

/**
 * Extension ...
 *
 * @defgroup Foo Foo
 */
Foo::load();

/**
 * @codeCoverageIgnore
 */
class Foo {

	/**
	 * @note It is expected that this function is loaded before LocalSettings.php
	 * to ensure that settings and global functions are available by the time
	 * the extension is activated.
	 */
	public static function load() {
		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Extension.json/Schema#callback
	 */
	public static function initExtension( $credits = [] ) {
		define( 'FOO_VERSION', isset( $credits['version'] ) ? $credits['version'] : 'UNKNOWN' );
	}

	/**
	 * @since 1.0
	 */
	public static function onExtensionFunction() {

		if ( !defined( 'SMW_VERSION' ) ) {
			if ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) {
				die( "\nThe 'Foo' extension requires the 'Semantic MediaWiki' extension to be installed and enabled.\n" );
			} else {
				die( '<b>Error:</b> The <a href="https://">Foo</a> extension requires the <a href="https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki">Semantic MediaWiki</a> extension to be installed and enabled.' );
			}
		}

		// Do call the setup code
		// $hooks = new Hooks();
		// $hooks->register();
	}

}
</pre>
