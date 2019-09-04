# Installation guide (brief)

This is a brief installation and configuration guide for [Semantic MediaWiki](../README.md) (SMW)
that only contains the core steps. More verbose installation instructions with additional explanation
and upgrading instructions can be found [here](https://www.semantic-mediawiki.org/wiki/Help:Installation).

A list of supported PHP versions, MediaWiki versions and databases per SMW release can be found
in the [compatibility matrix](COMPATIBILITY.md).


## Download and installation

### Installation

The recommended way to install Semantic MediaWiki is with [Composer](https://getcomposer.org) using
[MediaWiki's built-in support for Composer](https://www.mediawiki.org/wiki/Composer).

#### Step 1

Change to the base directory of your MediaWiki installation. If you do not have a "composer.local.json" file yet,
create one and add the following content to it:

```
{
	"require": {
                  "mediawiki/semantic-media-wiki": "~3.1"
        }
}
```

If you already have a "composer.local.json" file add the following line to the end of the "require"
section in your file:

    "mediawiki/semantic-media-wiki": "~3.1"

Remember to add a comma to the end of the preceding line in this section.

#### Step 2

Run the following command in your shell:

    php composer.phar update --no-dev

Note if you have Git installed on your system add the `--prefer-source` flag to the above command.

#### Step 3

Add the following line to the end of your "LocalSettings.php" file:

    enableSemantics( 'example.org' );

Note that "example.org" should be replaced by your wiki's domain.

#### Step 4

Run the MediaWiki [update script](https://www.mediawiki.org/wiki/Manual:Update.php). The location of
this script is `maintenance/update.php`. It can be run as follows in your shell:

    php maintenance/update.php

#### Step 5

If you are installing SMW on a freshly installed wiki then you are done. If the wiki already has content
pages run the Semantic MediaWiki [data rebuild script](https://www.semantic-mediawiki.org/wiki/Help:Maintenance_script_"rebuildData.php"). The location of this script
is `extensions/SemanticMediaWiki/maintenance/rebuildData.php`. It can be run as follows in your shell:

    php extensions/SemanticMediaWiki/maintenance/rebuildData.php -v

### Installation without shell access

As an alternative to installing via Composer, you can obtain the SMW code by creating your own [individual file release](https://github.com/SemanticMediaWiki/IndividualFileRelease) most likely if command line access to the webspace is not available or if the hoster imposes restrictions on required functionality.

Note that SMW no longer provides file releases [(See #3347).](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/1732)

#### Step 1

Create your [individual file release](https://github.com/SemanticMediaWiki/IndividualFileRelease) using the respective script. Please pay attention to the MediaWiki version used in the script and adapt to your setup if necessary.

#### Step 2

Transfer the code thus compiled to the appropriate folders on your webspace.

#### Step 3

Add the following lines to the end of your "LocalSettings.php" file:

    enableSemantics( 'example.org' );

Note that "example.org" should be replaced by your wiki's domain.

#### Step 4

Log in as a user with administrator permission to your wiki and go to the "Maintenance" tab on special page "Special:SemanticMediaWiki":

Click on the "Initialise or upgrade tables" button in the "Database maintenance" section to setup the
database.

#### Step 5

If you are installing SMW on a freshly installed wiki then you are done. If the wiki already has content
pages also do the following on page "Special:SemanticMediaWiki":

Click on the "Start updating data" button in the "Data rebuild" subsection of "Maintenance" tab
to activate the [automatic data update](https://www.semantic-mediawiki.org/wiki/Help:Repairing_SMW's_data).

### Installation of development versions and release candidates

If you would like to install a development version or release candidate then replace the lines as stated in step 3 of the
"Installation with Composer" section with the following line

* master: `"mediawiki/semantic-media-wiki": "@dev"`
* legacy branch: `"mediawiki/semantic-media-wiki": "3.0.x@dev"`
* release candidate: `"mediawiki/semantic-media-wiki": "~3.1@rc"`

## More instructions

* [Verbose installation instructions](https://www.semantic-mediawiki.org/wiki/Help:Installation)
* [Upgrading instructions](https://www.semantic-mediawiki.org/wiki/Help:Installation#Upgrading)
* [Configuration instructions](https://www.semantic-mediawiki.org/wiki/Help:Configuration)
* [Administrator manual](https://www.semantic-mediawiki.org/wiki/Help:Administrator_manual)
