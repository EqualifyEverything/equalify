# Equalify Contributing Guide

_Limits express Equalify possibilities._

## Equalify's Mission

Equalify aims to efficiently manage countless accessibility issues, so that accessibility administerators can resolve issues with quicker speed and accuracy than ever before. The project also intends to inspire a diverse community of contributors. 

We can achieve our goals by creating well-documented, open code, and a culture of collabroation that rewards experimentation as well as successful implementation.

## Judging a Pull Request

Pull requests (PRs) are only merged if the reviewer can answer "yes" to each of these five questions:

1. **Is the PR's code understandable to a junior PHP developer?** Explicit commenting and naming conventions are key. Equalify code should be understandable to developers with experience building PHP projects like WordPress plugins and Drupal modules.
2. **Does PR code work with the GNU AGPL?** Equalify is licensed under the [GNU Affero General Public License](/LICENSE). All new code must abide by the rules of the license.
3. **Can Equalify users spinup services that PR code relies on?** Third-party services should be easy to spinup. Equalify does not PRs that integrate with codebases that are willfully abstracted or poorly documented.
4. **Does PR code work within or simplify our official installation process?** Equalify's [Easy Install](/README.md#easy-install) is straightforward to folks who've installed WordPress. Future releases of Equalify should further simplify that process. 
5. **Does the pull request introduce a useful update?** Equalify aims to be useful. All features and information should solve problems for our users.
6. **Does the update maintain compliance with our [Accessibility Statement](/ACCESSIBILITY.md)?** Every update must adhear to our accessibility standards.

## Why PHP? Why are the WordPress and Drupal communities a model?
Equalify is built for folks who work with web content. Since PHP is argueably the most popular web programming language and since WordPress + Drupal are the most popular content managent systems, Equalify is designed to work with that language and appeal to WordPress + Drupal users.

## Coding Standards

Most of the contributors to the project do WordPress development, so we follow [WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards).

We're up for any new coding standards!

## Usable Hooks & Patterns.

Check out [/models/hooks.php](/models/hooks.php). We plan to add more hooks over time (see issue #174).

## Coding an Integration

Integrations process URLs that Equalify scans. Use our [guide to coding an integration](https://github.com/bbertucc/equalify/wiki/Coding-an-Integration) to build your integration.

## Testing
The unit tests are located in the `tests` directory.
To run them, navigate to the root of the project and run the following command in a terminal:
`./vendor/bin/phpunit tests`

## Progress Depends on Bugs

The future of Equalify depends on the issues that you report. Check out the [Help Wanted](https://github.com/bbertucc/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) and [Good First Issue](https://github.com/bbertucc/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) tags for issues that we need particular help on.

With your help, we can equalify the internet.
