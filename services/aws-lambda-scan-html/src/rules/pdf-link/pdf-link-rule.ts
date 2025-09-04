import axe from "axe-core";


export const pdfLinkRule:axe.Rule = {
  id: "pdf-link",
  enabled: true,
  metadata: {
    description: "This link is to a PDF document, which should be reviewed for WCAG compliance!",
    help: "WCAG (Web Content Accessibility Guidelines) also applies to PDF documents, and all PDF links should be checked for conformance.",
    helpUrl: "https://allyant.com/wcag-2-1-and-pdf-accessibility/",
  },
  impact: "serious" ,
  //tags: ["wcag2aa"],
  reviewOnFail: true,
  selector: 'a[href$=".pdf"]', 
  any: [],
  all: [],
  none: ["hidden-content"], // Note: this is a hack lol
};
