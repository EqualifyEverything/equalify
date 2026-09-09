This page is intended for **all users** as a quick reference for common technical and procedural queries. It covers specific edge cases, such as scanning PDFs stored in Box, interpreting WCAG 2.1 AA tags, and the current limitations of automated site crawling.

## Question (Q) & Answer (A)
**Q:** Does Equalify crawl my site automatically?<br>
**A:** No, not at this time.

**Q:** Equalify GitHub has a lot of repositories. Where should I start?<br>
**A:** https://github.com/equalifyeverything/equalify is the central hub.

**Q:** Does Equalify support XML format upload for sitemaps?<br>
**A:** Not currently, please track feature requests for future updates. 

**Q:** Can PDFs within Box be scanned?<br>
**A:** For Box files, by clicking Share > Link Settings >Allow Download in Box, it is possible to generate a direct download link which can be inputted into Equalify.

**Q:** If I enter a homepage URL, does Equalify scan all subpages automatically?<br>
**A:** No, not at this time.

**Q:** Can a team submit the PDF URL to the scan manually?<br>
**A:** If your PDF is publicly accessible, you could put that in as a URL and scan that.

**Q:** Can I run the PDF checker on my own device before publishing it online?<br>
**A:** No. Equalify requires a URL to scan your file. However, you can use a "hidden" URL that lacks an authentication layer (such as a password or login screen). As long as the URL is reachable by Equalify, the file does not need to be searchable or indexed by search engines.

**Q:** Will Equalify scan all PDFs in the Red media directory, or only those linked from a publicly accessible page?<br>
**A:** Equalify scans any PDF surfaced within a "Red" instance, including those found in document bodies, footers, or metadata. It does not limit scans to publicly linked files. 

**Q:** How do I look at only WCAG 2.1 AA failures for Web tests?<br>
**A:** Use the “wcag2aa” AND  “wcag21aa” tags to view all WCAG 2.1 AA issues found.
