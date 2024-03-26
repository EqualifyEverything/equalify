# Contributing
Equalify code is first and foremost inclusive. We invite everyone, no matter their experience, to contribute to Equalify.

This document outlines how we maintain an inclusive code base, open to all contributors.

## Key Guidelines
These guidelines are used to assess new code:

1. **Work Procedurally**: Procedural programming works with the basic Accessibility premise of well-ordered content (for more, check out [Mozilla’s discussion on “Proper Semantics”](https://developer.mozilla.org/en-US/docs/Learn/Accessibility/HTML#good_semantics)). We know that’s different than many software projects that value Object-oriented programming, but we have enjoyed the fact that many new-to-PHP users understand our code easily.
2. **Name Clearly**: Lots of code comments often mean that functions and variables are not named. We value explicit naming of functions and variables instead of adding lots of comments about what the functions or variables do.
3.** Write in PHP**: PHP became our programming language of choice after building early prototypes in Python and JavaScript. We didn’t choose Python because it wasn’t familiar to many website developers we started working with. We didn’t choose JavaScript because promoted working in a way that worked against screen reader users. We always remain open to change if that means making our platform’s code more accessible to users.
4. **Avoid Frameworks**: New frameworks must save us time without adding new barriers for contributors. Under that creed, we find ourselves going back to coding solutions in basic PHP instead of adopting frameworks. 
5. **Be Efficient, But Not At Expense of Clarity. **Remember: we are an accessibility platform. We want to be fast and agile. That said, we’ll gladly trade a small efficiency to be more clear to our contributors. We think the smartest solutions are both super efficient and super understandable.
6. **Act Without Dogma**: Of course, all of our ideas are up for debate! Please create a pull request to let us know of any new ideas. We’re excited to evolve into the most inclusive platform to have ever shaped the internet.

## Ready to get started?
Here are some steps to start contributing:

1. Add a new ticket to [issues](https://github.com/EqualifyEverything/equalify/issues) or create a pull request for changes.
2. @bbertucc is the principal maintainer, and he'll chime in on any new issues or PR with next steps.
3. Followup! Feel free to follow up on any issue or PR. Some things slip through the cracks.
4. Usually a discussion ensues before some clear tasks are assigned or a PR is approved.
5. Approved PRs and work will be tested before entering the `main` branch and turned into a release.

## Questions?
Open up a new issue with any question. Maintainers or the community will answer.

## Equalify API

The API can be accessed at accessible at `api.php`. All output is in [STREAM](https://github.com/EqualifyEverything/STREAM).

### Requests
You can request data by passing URL IDs to the API like this:
```
api.php?url_ids=2,3
```