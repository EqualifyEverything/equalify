This page is intended for **all users** running scans and need help resolving errors that occur during the scan process. While most scans complete without issue, you may occasionally encounter Scan Errors. These occur when the crawler is unable to successfully reach or process a specific URL in your list.
## Identifying Errors
If issues were detected during a scan, an alert badge will appear directly below the scan timestamp indicating the number of errors (e.g., "80 Errors During Scan").<br>
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/f06744bf2795951ea4cfe4894213a68fa8f6e494/user/User%20Guide%20Images/Sec%203_Scan%20Errors.png" alt="A summary box for a website audit showing 1,396 URLs included. The last scan is marked as complete for February 25, 2026, and includes a red warning badge stating 80 Errors During Scan." width="500"/><br>
## Viewing Error Details
To investigate why specific pages failed:
1. Click the Errors During Scan badge.
1. A Scan Errors modal will appear, listing every URL that failed.
1. Each entry includes the specific URL and a "View error details" dropdown.
1. Review the technical log (e.g., "Execution context was destroyed") to determine if the issue is a broken redirect, a server timeout, or a page that requires authentication.
```tip
💡Tip: Some scan errors are temporary. If you see a high number of failures, try re-running the scan during off-peak hours to ensure server stability. If the error persists over an extended period, please file a bug report or reach out to your administrator.
```
<img src="https://github.com/EqualifyEverything/equalify-docs/blob/f06744bf2795951ea4cfe4894213a68fa8f6e494/user/User%20Guide%20Images/Sec%203_Scan%20Errors%20Detail.png" alt="Scan Errors modal with list of failed URLs. One entry shows a Scan Failed badge for a UIC dentistry intranet URL with a technical error message: Execution context was destroyed, most likely because of a navigation." width="500"/>
