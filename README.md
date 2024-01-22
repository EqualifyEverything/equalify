<img src="logo.svg" alt="Equalify Logo" width="300">

## Better Accessibility Management
Equalify aims to be the most useful accessibility platform. That means faster scanning, more accurate results, and a more intuitive user interface. We publish Equalify code here so that you can run the platform locally, building new features and contributing issues.

## Managed Service
Not technical? Want to support Equalify?

Visit [https://equalify.app](https://equalify.app) to try our hosted service.

The service is <strong>fully supported</strong> and <strong>super fast</strong>. Plus, you'll get these features:
- Automatic Scans
- Scheduled Scans
- Multi-User Administration
- Shareable Reports

And please <b>star this repo</b>!

Your support sustains open source work.

## Setup
After forking the repo: 
1. Create `.env` with the following:
    ```
    ## DB Info
    DB_HOST=
    DB_USERNAME=
    DB_PASSWORD=
    DB_NAME=
    DB_PORT=

    ## Scan Info
    SCAN_URL=
    ```
2. Run in your favorite local LAMP/LEMP setup. (We love [ddev](https://github.com/ddev/ddev)!)
3. Run `php actions/install.php` to create the tables.
4. Equalify everything!

## Contribute
Submit bug reports, questions, and patches to the repo's [issues](https://github.com/EqualifyEverything/equalify/issues) tab.

If you would like to submit a pull request, please read [ACCESSIBILITY.md](/ACCESSIBILITY.md) before you do.

## Special Thanks
A chaos wizard 🧙, [Bruno Lowagie](https://lowagie.com), and many others help Equalify. The project is run by [@bbertucc](https://github.com/bbertucc). Special shout out to [Pantheon](https://pantheon.io/) and [Little Forest](https://littleforest.co.uk/feature/web-accessibility/) for providing funding for [bounties](https://github.com/bbertucc/equalify/issues?q=is%3Aopen+is%3Aissue+label%3Abountied). Yi, Kate, Bill, Dash, Sylvia, Anne, Doug, Matt, Nathan, and John- You are the brains that helped launch this idea. [@ebertucc](https://github.com/ebertucc) and [@jrchamp](https://github.com/jrchamp) are the project's first contributors - woot woot! Much help also came from [mgifford](https://github.com/mgifford), [kreynen](https://github.com/kreynen), and [j-mendez](https://github.com/j-mendez) - you all rock! [Guzzle](https://github.com/guzzle/guzzle) makes multiple concurrent scans possible. [Composer](https://getcomposer.org/) makes Guzzle possible. We're now adding [Symfony](https://symfony.com) components to the project. [TolstoyDotCom](https://github.com/TolstoyDotCom) and [zersiax](https://github.com/zersiax) were our first hired contributors. [azsak](https://github.com/azdak) currently keeps the scan chucgging. And of course shoutout to the [Decubing](https://github.com/decubing) team for making our MVP a Version Uno!

This project's code is published under the [GNU Affero General Public License v3.0](https://github.com/bbertucc/equalify/blob/main/LICENSE) to inspire new collaborations.

**Together, we can equalify the internet.**
