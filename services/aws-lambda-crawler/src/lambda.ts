import type { APIGatewayProxyEventV2, APIGatewayProxyResultV2 } from "aws-lambda";
import Sitemapper from "sitemapper";

interface CrawlRequest {
  url: string;
  depth?: number;
}

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "Content-Type",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
};

const respond = (statusCode: number, body: object): APIGatewayProxyResultV2 => ({
  statusCode,
  headers: { "Content-Type": "application/json", ...corsHeaders },
  body: JSON.stringify(body),
});

export const handler = async (
  event: APIGatewayProxyEventV2
): Promise<APIGatewayProxyResultV2> => {
  // Handle CORS preflight
  if (event.requestContext?.http?.method === "OPTIONS") {
    return respond(200, {});
  }

  try {
    const body: CrawlRequest = JSON.parse(event.body || "{}");

    if (!body.url) {
      return respond(400, { error: "url is required" });
    }

    // Normalize URL — ensure it has a protocol
    let targetUrl = body.url.trim();
    if (!targetUrl.startsWith("http://") && !targetUrl.startsWith("https://")) {
      targetUrl = `https://${targetUrl}`;
    }

    // Try sitemap discovery first
    const sitemapper = new Sitemapper({
      url: `${targetUrl.replace(/\/$/, "")}/sitemap.xml`,
      timeout: 15000,
    });

    const { sites } = await sitemapper.fetch();

    if (sites.length > 0) {
      return respond(200, {
        url: targetUrl,
        method: "sitemap",
        urls: sites,
      });
    }

    // No sitemap found — return just the original URL
    return respond(200, {
      url: targetUrl,
      method: "none",
      urls: [targetUrl],
    });
  } catch (error) {
    console.error("Crawl error:", error);
    return respond(500, {
      error: "Crawl failed",
      message: error instanceof Error ? error.message : String(error),
    });
  }
};
