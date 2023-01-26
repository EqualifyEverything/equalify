# Equalify the web!

**96.8% of home pages have WCAG conformance failures**.[^1] Failing WCAG conformance means countless people with disabilities do not have equal access to the internet.

**Equalify aims to make websites more accessible.** We are building an affordable and easy-to-use platform that integrates your favorite WebOps. All of our work is open source, published under the [AGPL](https://github.com/bbertucc/equalify/blob/main/LICENSE). Working this way helps us create the most accessible platform for internet accessibility. 

## Support Equalify

⭐ **Star the repo** to show your support.

🌸 **Try our managed service**, [equalify.app](https://equalify.app/).

🛠️ **Contribute a pull request** or [new issue](https://github.com/bbertucc/equalify/issues).

🎩 **[Sponsor / Donate](https://github.com/sponsors/bbertucc)** to Equalify.

## How will Equalify increase content accessibility?

Accessibility is a diverse category of work. Equalify focuses on [Web Accessibility Guidlines Version 2.1](https://www.w3.org/TR/WCAG21/) (WCAG) standards. We are building tools that help website managers align their sites with WCAG guidelines. Our tools will integrate automated WCAG testing services, web services, and teaching materials into one WebOps platform.

<img width="1316" alt="Equalify's integrations page that includes logos of Pantheon, Drupal, URLBox, WAVE, WordPress, and XML Sitemaps." src="https://user-images.githubusercontent.com/46652/198109576-6c2810f8-65c7-4a67-b584-cafd18138153.png">

## What does Equalify currently do?

The app currently scans websites for WCAG errors.

You can import pages from WordPress, XML sitemaps, and single URLs. Equalify then crawls all your pages for WCAG 2.1 errors using the popular [WAVE scan](https://wave.webaim.org/).

Every alert is reported on a filterable dashboard.

<img width="1316" alt="Equalify's reporting dashboard that lists different alerts including Missing Alt Text, Missing Form Label, and Very Log Contrast alerts" src="https://user-images.githubusercontent.com/46652/198109248-36343405-9e89-48b7-ac9f-ee0c0d830859.png">

## Download and Use
1. Download or clone [the latest release](https://github.com/bbertucc/equalify/releases).
2. Change `sample-config.php` to `config.php` and update info.
3. Run `composer install` to install Composer dependencies (Composer v. 2.4.4 required).
4. Upload/run on a Linux server (PHP 8.1 + MySQL required).
5. Report [issues](https://github.com/bbertucc/equalify/issues), questions, and patches.

**Not a technical user?** Use Equalify now at [equalify.app](https://equalify.app/).

For more information, checkout the [Equalify FAQs](https://github.com/bbertucc/equalify/wiki/Equalify-FAQs/).

## Special Thanks
A chaos wizard 🧙 and many others help Equalify. Special shout out to [Pantheon](https://pantheon.io/) and [Little Forest](https://littleforest.co.uk/feature/web-accessibility/) for providing funding for [bounties](https://github.com/bbertucc/equalify/issues?q=is%3Aopen+is%3Aissue+label%3Abountied). Yi, Kate, Bill, Dash, Sylvia, Anne, Doug, Matt, Nathan, and John- You are the brains that helped launched this idea.  [@ebertucc](https://github.com/ebertucc) and [@jrchamp](https://github.com/jrchamp) are the project's first contributors - woot woot! [Guzzle](https://github.com/guzzle/guzzle) makes multiple concurrent scans possible. [Composer](https://getcomposer.org/) makes Guzzle possible.

<p>Hosting supported by:</p>
<p>
  <a href="https://www.digitalocean.com/">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="201px">
  </a>
</p>


This project is Open Source under [AGPL](https://github.com/bbertucc/equalify/blob/mvp-1.2/LICENSE) to inspire new collaborations.

**Together, we can equalify the internet.**

-[@bbertucc](https://github.com/bbertucc)

[^1]:[The WebAIM Million](https://webaim.org/projects/million/)
