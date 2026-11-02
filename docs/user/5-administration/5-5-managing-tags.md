This page is intended for **Administrators** who keep their workspace's audit tags organized. The Tags tab is only visible to Admin users and is accessed from **Account > Tags**. For how tags are created and used on audits, see [🔗 "Organizing audits with tags"](https://equalify.uic.edu/dashboard/user-guide/2-configuring-and-running-audits/2-5-organizing-audits-with-tags).

## Overview
Any workspace member can create tags, so over time a workspace may end up with duplicates, misspellings, or tags that are no longer used. The Tags tab gives Admins a breakdown of every tag and the audits that use it, along with tools to rename, merge, and delete tags.

The top of the tab shows how many tags exist and how many audits are tagged. Use the **Search Tags** field to find a tag by name.

## The tags table
| Column | Description |
|---|---|
| Tag | The tag's name. Select it to show or hide the list of audits using the tag. |
| Audits | The number of audits that have this tag. |
| Blockers (latest scans) | The total number of blockers found in the most recent scan of each audit with this tag. |
| Created | The date the tag was created. |
| Actions | Buttons to rename, merge, or delete the tag. |

When you expand a tag, each audit is listed with a link to its page, the name of the person who created it, how often it runs, and its current number of blockers. Tags that aren't used by any audits show "No audits use this tag", which makes unused tags easy to spot and clean up.

## Renaming a tag
1. Navigate to **Account > Tags**.
2. Select the **pencil** icon next to the tag.
3. Enter the new name and select **Rename**.

The new name appears on every audit that uses the tag.

```important
⚠️Important: You can't rename a tag to a name that another tag already uses (names are not case-sensitive). If you want to combine the two tags, use Merge instead.
```

## Merging tags
Merging combines two tags into one. Use it when two tags mean the same thing, such as "Athletics" and "Sports".
1. Navigate to **Account > Tags**.
2. Select the **merge** icon next to the tag you want to remove (for example, "Sports").
3. In the **Merge into** dropdown, choose the tag you want to keep (for example, "Athletics"). The window explains how many audits will be affected.
4. Select **Merge**.

Every audit that had the first tag now has the tag you kept, and the first tag is deleted. Audits that already had both tags simply keep the one you kept.

```tip
💡Tip: The merge button is unavailable when your workspace has only one tag, since there's nothing to merge into.
```

## Deleting a tag
1. Navigate to **Account > Tags**.
2. Select the **trash can** icon next to the tag.
3. Review how many audits the tag will be removed from, then select **Delete Tag**.

Deleting a tag removes it from every audit that uses it. The audits themselves, and their scans and results, are not affected.

```important
⚠️Important: Deleting or merging a tag can't be undone. If you delete a tag by mistake, you'll need to create it again and re-add it to each audit.
```

## Tag changes in the Logs
Renaming, merging, and deleting tags are all recorded on the Logs page, along with the Admin who made the change. Merge and delete entries also note how many audits were affected. See [🔗 "What gets logged"](https://equalify.uic.edu/dashboard/user-guide/4-compliance-and-logs/4-2-what-gets-logged-and-the-importance-of-audit-logs).
