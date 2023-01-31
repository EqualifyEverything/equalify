# Equalify the web!

**96.8% of home pages have WCAG conformance failures**.[^1] Failing WCAG conformance means countless people with disabilities do not have equal access to the internet.

**Equalify aims to make websites more accessible.** We are building a useful and useable platform to find and fix website accessibility issues. All of our work is open source, published under the [GNU AGPL](https://github.com/bbertucc/equalify/blob/main/LICENSE).

## Support Equalify

⭐ **Star the repo** to show your support.

🌸 **Try our managed service**, [equalify.app](https://equalify.app/).

🛠️ **Contribute a pull request** or [new issue](https://github.com/bbertucc/equalify/issues).

🎩 **[Sponsor / Donate](https://github.com/sponsors/bbertucc)** to Equalify.

## What does Equalify currently do?

The app currently scans websites for  violations of the [Web Accessibility Guidlines Version 2.1](https://www.w3.org/TR/WCAG21/) (WCAG). 

You can import pages from WordPress, XML sitemaps, and single URLs. Equalify then crawls all your pages for WCAG 2.1 errors using the popular open-source scanning tools, like [axe-core](https://github.com/dequelabs/axe-core).

Every alert is reported on a filterable dashboard.

<img width="1316" alt="Equalify's reporting dashboard that lists different alerts including Missing Alt Text, Missing Form Label, and Very Log Contrast alerts" src="https://user-images.githubusercontent.com/46652/198109248-36343405-9e89-48b7-ac9f-ee0c0d830859.png">

## Download and Use
1. Download or clone [the latest release](https://github.com/bbertucc/equalify/releases).
2. Change `sample-config.php` to `config.php` and update info.
3. Run `composer install` to install Composer dependencies (Composer v. 2.4.4 required).
4. Upload/run on a Linux server (PHP 8.1 + MySQL required).

## Contribute
The easiest way to contirbute is to report bugs, questions, and patches in our [issues](https://github.com/bbertucc/equalify/issues) tab.

If you would like to submit a pull request, read [CONTRIBUTING.md](/CONTRIBUTING.md) for information on coding guidelines and how we judge pull requests.

**Not a technical user?** Use Equalify now at [equalify.app](https://equalify.app/).

For more information, checkout the [Equalify FAQs](https://github.com/bbertucc/equalify/wiki/Equalify-FAQs/).

## Special Thanks
A chaos wizard 🧙 and many others help Equalify. The project is run by [@bbertucc](https://github.com/bbertucc). Special shout out to [Pantheon](https://pantheon.io/) and [Little Forest](https://littleforest.co.uk/feature/web-accessibility/) for providing funding for [bounties](https://github.com/bbertucc/equalify/issues?q=is%3Aopen+is%3Aissue+label%3Abountied). Yi, Kate, Bill, Dash, Sylvia, Anne, Doug, Matt, Nathan, and John- You are the brains that helped launched this idea. [@ebertucc](https://github.com/ebertucc) and [@jrchamp](https://github.com/jrchamp) are the project's first contributors - woot woot! Much help also came from [mgifford](https://github.com/mgifford), [kreynen](https://github.com/kreynen), and [j-mendez](https://github.com/j-mendez) - you all rock! [Guzzle](https://github.com/guzzle/guzzle) makes multiple concurrent scans possible. [Composer](https://getcomposer.org/) makes Guzzle possible.

<p>Hosting supported by:</p>
<p>
  <a href="https://www.digitalocean.com/">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="201px">
  </a>
</p>

This project is Open Source under [AGPL](https://github.com/bbertucc/equalify/blob/mvp-1.2/LICENSE) to inspire new collaborations.

**Together, we can equalify the internet.**

[^1]:[The WebAIM Million](https://webaim.org/projects/million/)
