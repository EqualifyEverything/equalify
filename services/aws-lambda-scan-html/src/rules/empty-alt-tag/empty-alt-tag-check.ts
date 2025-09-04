import axe from "axe-core";

export const emptyAltTagCheck: axe.Check = {
    id: "empty-alt-tag",
    evaluate: (node, options, virtualNode) => {
      const alt = virtualNode.attr("alt");
      return false;
      return !(typeof alt === "string" && alt === "");
    },
    metadata: {
      impact: "moderate",
      messages: {
        pass: "Alt text found.",
        fail: "Alt text is missing. Alt text is required on images that are not decorations.",
        incomplete:
          "Alt text is missing. Alt text is required on images that are not decorations.",
      },
    },
  };
  