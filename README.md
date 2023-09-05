# Semantic MediaWiki

[![CI](https://github.com/SemanticMediaWiki/SemanticMediaWiki/actions/workflows/main.yml/badge.svg)](https://github.com/SemanticMediaWiki/SemanticMediaWiki/actions/workflows/main.yml)
[![Latest Stable Version](https://poser.pugx.org/mediawiki/semantic-media-wiki/version.png)](https://packagist.org/packages/mediawiki/semantic-media-wiki)
[![Packagist download count](https://poser.pugx.org/mediawiki/semantic-media-wiki/d/total.png)](https://packagist.org/packages/mediawiki/semantic-media-wiki)

**Semantic MediaWiki** (a.k.a. SMW) is a free, open-source extension to [MediaWiki](https://www.semantic-mediawiki.org/wiki/MediaWiki) – the wiki software that
powers Wikipedia – that lets you store and query data within the wiki's pages.

Semantic MediaWiki is also a full-fledged framework, in conjunction with
many spinoff extensions, that can turn a wiki into a powerful and flexible
knowledge management system. All data created within SMW can easily be
published via the [Semantic Web](https://www.semantic-mediawiki.org/wiki/Semantic_Web),
allowing other systems to use this data seamlessly.

For a better understanding of how Semantic MediaWiki works, have a look at [deployed in 5 min](https://vimeo.com/82255034)
and the [Sesame](https://vimeo.com/126392433), [Fuseki ](https://vimeo.com/118614078) triplestore video, or
browse the [wiki](https://www.semantic-mediawiki.org) for a more comprehensive introduction.

## Requirements

Semantic MediaWiki requires MediaWiki and its dependencies, such as PHP.

Supported MediaWiki, PHP and database versions depend on the version of Semantic MediaWiki.
See the [compatibility matrix](docs/COMPATIBILITY.md) for details.

## Installation

The recommended way to install Semantic MediaWiki is by using [Composer][composer]. See the detailed
[installation guide](docs/INSTALL.md) as well as the information on [compatibility](docs/COMPATIBILITY.md).

## Documentation

Most of the documentation can be found on the [Semantic MediaWiki wiki](https://www.semantic-mediawiki.org).
A small core of documentation also comes bundled with the software itself. This documentation is minimalistic
and less explanatory than what can be found on the SMW wiki. It is however always kept up to date, and applies
to the version of the code it comes bundled with. The most important files are linked below.

* [User documentation](docs/README.md)
* [Technical documentation](docs/technical/README.md)
* [Hacking Semantic MediaWiki](docs/architecture/README.md)

## Support

[![Chatroom](https://www.semantic-mediawiki.org/w/thumb.php?f=Comment-alt-solid.svg&width=35)](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_chatroom)
[![Twitter](https://www.semantic-mediawiki.org/w/thumb.php?f=Twitter-square.svg&width=35)](https://twitter.com/#!/semanticmw)
[![Facebook](https://www.semantic-mediawiki.org/w/thumb.php?f=Facebook-square.svg&width=35)](https://www.facebook.com/pages/Semantic-MediaWiki/160459700707245)
[![LinkedIn](https://www.semantic-mediawiki.org/w/thumb.php?f=LinkedIn-square.svg&width=35)]([https://twitter.com/#!/semanticmw](https://www.linkedin.com/groups/2482811/))
[![YouTube](https://www.semantic-mediawiki.org/w/thumb.php?f=Youtube-square.svg&width=35)](https://www.youtube.com/c/semanticmediawiki)
[![Mailing lists](https://www.semantic-mediawiki.org/w/thumb.php?f=Envelope-square.svg&width=35)](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_mailing_lists)

Primary support channels:

* [User mailing list](https://sourceforge.net/projects/semediawiki/lists/semediawiki-user) - for user questions
* [SMW chat room](https://www.semantic-mediawiki.org/wiki/Semantic_MediaWiki_chatroom) - for questions and developer discussions
* [Issue tracker](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues) - for bug reports

## Contributing

Many people have contributed to SMW. A list of people who have made contributions in the past can
be found [here][contributors] or on the [wiki for Semantic MediaWiki](https://www.semantic-mediawiki.org/wiki/Help:SMW_Project#Contributors).
The overview on [how to contribute](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/CONTRIBUTING.md)
provides information on the different ways available to do so.

If you want to contribute work to the project please subscribe to the developers mailing list and
have a look at the contribution guidelines.

* [File an issue](https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues)
* [Submit a pull request](https://github.com/SemanticMediaWiki/SemanticMediaWiki/pulls)
* Ask a question on [the mailing list](https://www.semantic-mediawiki.org/wiki/Mailing_list)

## Tests

This extension provides unit and integration tests and are normally run by a [continuous integration platform][travis]
but can also be executed locally using the shortcut command `composer phpunit` from the extension base directory. A more
comprehensive introduction can be found under the [test section](/tests/README.md#running-tests).

## License

[GNU General Public License, version 2 or later][gpl-licence]. The COPYING file explains SMW's copyright and license.

[contributors]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/graphs/contributors
[travis]: https://travis-ci.org/SemanticMediaWiki/SemanticMediaWiki
[mw-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[gpl-licence]: https://www.gnu.org/copyleft/gpl.html
[composer]: https://getcomposer.org/
[smw-installation]: https://www.semantic-mediawiki.org/wiki/Help:Installation
