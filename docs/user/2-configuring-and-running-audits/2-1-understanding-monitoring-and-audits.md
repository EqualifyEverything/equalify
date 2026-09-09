This page is intended for **Site Administrators and Project Leads** to understand the foundational concept of "Blockers" and their relationship with the Web Content Accessibility Guidelines (WCAG). Furthermore, the importance of understanding the root causes of "Blockers" and how a single fix can address hundreds of site issues.

## Blockers explained for non-technical users
When you see the word “Blocker” in an accessibility report, it can sound alarming. But don’t worry - blockers are not always cause for alarm. A Blocker is an issue that may prevent someone from entirely using your website or app, especially people who rely on assistive technologies such as screen readers, keyboard navigation, or voice control.
Think of it like a locked door in a building. A person blocked by that door cannot access what is inside.

Having multiple blockers in a report does not automatically mean:
- Your site is unusable
- Your team did a poor job
- You need to rebuild everything

Sometimes:
- A blocker affects only one specific interaction.
- A blocker may not impact your users.
- Multiple blockers may stem from the exact same root cause.
In most cases, blockers are fixable - especially once you understand what’s behind them.

## Are blockers failures of WCAG?
The World Wide Web Consortium (W3C) sets accessibility standards through the Web Content Accessibility Guidelines (WCAG).

WCAG provides technical guidelines for websites to ensure accessibility.
However:
- WCAG uses technical language.
- It applies to a wide range of technologies and situations.
- Automated tools may flag issues that require human review.
Because these tools cannot always interpret context, they may label some blockers as technically non-compliant.
- But low impact in real-world use
- Or flagged due to structural patterns in code
So a blocker does not always mean “users are stuck.” It means “this needs attention.”

## How one fix can solve hundreds of issues 
Some blockers come from repeated components, such as:
- Buttons
- Navigation menus
- Forms
- Templates
- Design system components
One incorrectly coded button component can create 200 blockers if it appears 200 times across your site.

However, you don’t need 200 fixes. You need one correct fix at the source. Fix the core component, and every instance improves.
