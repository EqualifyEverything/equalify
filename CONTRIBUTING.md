# Equalify Contributing Guide

_These limits expand Equalify possibilities._

## Equalify's Mission

Equalify aims to efficiently manage countless accessibility issues so that accessibility administrators can resolve issues with quicker speed and accuracy than ever before. The project also intends to inspire a diverse community of contributors. 

We can achieve our goals by creating well-documented, open code, and a culture of collaboration that rewards experimentation as well as successful implementation.

## Judging a Pull Request

Pull requests (PRs) are only merged into an official release if the reviewer can answer "yes" to each of these five questions:

1. **Is the PR's code understandable to a junior PHP developer?** Explicit commenting and naming conventions are key. Equalify code should be understandable to developers with experience building PHP projects like WordPress plugins and Drupal modules.
2. **Does PR code work with the GNU AGPL (Version 3)?** Equalify is licensed under Version 3 of the [GNU Affero General Public License](/LICENSE). All new code must abide by the rules of the license.
3. **Can Equalify users spin up services that the PR code relies on?** Third-party services should be easy to run. Equalify does not approve PRs that integrate with codebases that are willfully abstracted or poorly documented. We also don't approve PRs that rely on proprietary code. For example: we won't approve a PR that relies on an API that Equalify users can't easily run their own local machine.
4. **Does PR code work within or simplify our official installation process?** Equalify's [Easy Install](/README.md#easy-install) is straightforward to folks who've installed WordPress. Future releases of Equalify should further simplify that process. 
5. **Does the pull request introduce a useful update?** Equalify aims to be useful. All features and information should solve problems for our users.
6. **Does the update maintain compliance with our [Accessibility Statement](/ACCESSIBILITY.md)?** Every update must adhere to our accessibility standards.

Also, if your contribution adds a significant amount of unique code, you will be asked to sign a contributor agreement. These agreements protect both contributors and Equalify. Our contributor agreement uses language in the [Apache Contributor Agreement](https://www.apache.org/licenses/icla.pdf). 

## Why PHP? Why is WordPress a model?

Equalify is built for folks who work with web content. Since PHP is arguably the most popular web programming language and since WordPress is the most popular content management system, Equalify is designed to work with that language and appeal to WordPress users.

## Why aren't we using a framework like Laravel?

Equalify is intended to be a platform of its own. We want users to quickly be able to start creating their own integrations and update Equalify's core codebase. To satisfy that mission, Equalify was coded using basic PHP. Any user who knows basic PHP should be able to contribute to Equalify.

## Coding Standards

Most of the contributors to the project have done WordPress development, so we follow [WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards). We want new coding standards! Create a PR or issue to request a change.

## Coding an Integration

Integrations process URLs that Equalify scans. Use our [guide to coding an integration](https://github.com/EqualifyEverything/equalify/wiki/Coding-an-Integration) to build your integration.

## Progress Depends on Issues

The future of Equalify depends on the issues that you report. Check out the [Help Wanted](https://github.com/EqualifyEverything/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) and [Good First Issue](https://github.com/EqualifyEverything/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) tags for issues that we need particular help on. 

With your help, we can equalify the internet.