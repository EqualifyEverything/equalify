# Equalify Contributing Guide

This document shares limits that sculpt Equalify possibilities. 

## Equalify's Mission

Equalify aims to efficiently manage countless accessibility issues, so that accessibility administerators can resolve issues with quicker speed and accuracy than ever before.

## Judging a Pull Request

Pull requests will only be merged if the reviewer can answer "yes" to each of these five questions:

1. **Is the new code understandable to a junior PHP developer?** Explicit commenting and naming conventions are key. Equalify code should be understandable to developers with experience building PHP projects like WordPress plugins and Drupal modules.
2. **Does the new code work with the GNU AGPL?** Equalify is licensed under the [GNU Affero General Public License](https://github.com/bbertucc/equalify/blob/main/LICENSE). All new code must abide by the rules of the license.
3. **Does the new code work within or simplify our official installation process?** Equalify's [official installation process](https://github.com/bbertucc/equalify#download-and-use) is straightforward to folks who've installed WordPress in the past. Future releases of Equalify should further simplify that process. 
4. **Does the pull request introduce a useful update?** Equalify aims to be the most useful accessibility platform. All features and information should be useful to our users.
5. **Does the update maintain compliance with our [Accessibility Statement](/ACCESSIBILITY.md)?** Every update must adhear to our accessibility standards.

## Coding Standards

Most of the contributors to the project do WordPress development, so we follow [WordPress coding standards](https://github.com/WordPress/WordPress-Coding-Standards).

We're up for any new coding standards!

## Usable Hooks & Patterns.

Check out [/models/hooks.php](/models/hooks.php). We plan to add more hooks over time (see issue #174).

## Coding an Integration

Integrations process URLs that Equalify scans. Use our [guide to coding an integration](https://github.com/bbertucc/equalify/wiki/Coding-an-Integration) to build your integration.

## Progress Depends on Bugs

The future of Equalify depends on the issues that you report. Check out the [Help Wanted](https://github.com/bbertucc/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22help+wanted%22) and [Good First Issue](https://github.com/bbertucc/equalify/issues?q=is%3Aissue+is%3Aopen+label%3A%22good+first+issue%22) tags for issues that we need particular help on.

With your help, we can equalify the internet.