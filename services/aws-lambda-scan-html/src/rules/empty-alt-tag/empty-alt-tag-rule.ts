import axe from "axe-core";


export const emptyAltTagRule:axe.Rule = {
  id: "empty-alt-tag",
  enabled: true,
  metadata: {
    description: "Check if an image has a non-empty alt tag",
    help: "Non-decorative images should have a non-empty alt tag",
    helpUrl: "https://www.w3.org/WAI/tutorials/images/decorative/",
  },
  impact: "moderate" ,
  tags: ["wcag2aa"],
  reviewOnFail: true,
  selector: "img",
  any: ["non-empty-alt"]
};
