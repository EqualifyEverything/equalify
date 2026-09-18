This page is intended for **Administrators** who need to customize the appearance of the platform or configure its AI-powered features. The System tab is only visible to Admin users and is accessed from **Account > System**.

## Co-Branding
The Co-Branding panel lets Admins display a custom logo in the Equalify interface, so the platform can reflect your organization's identity.

### Configuring co-branding
1. Navigate to **Account > System**.
2. In the **Co-Branding** panel, fill in the following fields:
    - **Logo URL:** A direct link to your organization's logo image (e.g., `https://example.com/logo.png`). The URL must use `https://`. Leave this field blank to remove the logo.
    - **Logo Alt Text:** A short description of the logo for screen readers (e.g., "University of Illinois Chicago logo"). Always provide alt text when a logo is set.
    - **Logo Size:** Use the slider or number input to set the display size of the logo in pixels. The size must be between 32 and 150 pixels.
3. Settings are saved automatically as you type — there is no Save button.

```tip
💡Tip: Always provide meaningful alt text for your logo. A logo without alt text creates an accessibility barrier for users who rely on screen readers — which is especially important for an accessibility platform.
```
```important
⚠️Important: Equalify cannot host your logo image. The Logo URL must point to an image already published on a publicly accessible server. If the URL becomes unavailable, the logo will stop displaying.
```

## LLM Blocker Summaries
The LLM Blocker Summaries panel controls whether Equalify generates AI-powered explanations and fix instructions on Issue Detail pages (see [3-2: Audit Report Detailed View](../3-interpreting-audit-data-and-dashboards/3-2-audit-report-detailed-view.md)). These summaries are generated using AWS Bedrock and are cached in the database after the first request.

### Enabling or disabling AI summaries
- Check **Enable LLM summaries** to turn the feature on for all users in the workspace.
- Uncheck it to disable all AI summary generation site-wide. Existing cached summaries will no longer be displayed while the feature is off.

Changes take effect immediately — no Save button is required.

### Choosing a model
The **Bedrock model** dropdown lists the AWS Bedrock foundation models available in your region that support on-demand inference and text output. Select the model you want Equalify to use when generating summaries.

```important
⚠️Important: Changing the model takes effect immediately for new or refreshed summaries. Existing cached summaries are not automatically regenerated — users can click "Reload summary" on an Issue Detail page to generate a fresh summary with the new model.
```
```tip
💡Tip: If the model list is empty, verify that your AWS Bedrock configuration grants access to at least one foundation model in the correct region.
```
