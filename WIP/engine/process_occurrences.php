<?php

// 

// compare occurrences against each other

// update occurrence status where needed
?>

Help me update process_scan.php.

Instead of just adding everything marked violation into queued_occurrence, i want to compare the results from the scan with data in the occurrences table. 

To understand how results data and data in the occurrences table relate look that this code:
```
'occurrence_code_snippet' => $node['html'],
'occurrence_message_id' =>  handle_message_db($violation['help'], $violation['description']),
'occurrence_property_id' => $property_id,
'occurrence_tag_ids' => handle_tags_db($violation['tags']),
'occurrence_page_id' =>  handle_page_db('https://temp.com'), // use $response['url']
'occurrence_source' => 'Equalify Scan',
```

If a row in the occurrence table has the same data, don't do anything with it.

If no row in the occurrence table has that data, add it with the occurrence_status = active. A new entry in updates should also be added with date_created being the current date and time, occurrence_id being the id from the row in the occurence table that was updated and update_message being "activated".

Also filter by page_id. If 


Let's create a new PHP script, process_scan.php. Before we begin, you're going to need a few pieces of info:

1. We query the DB using:
```
if(!defined('__ROOT__'))
    define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/db.php'); 
```
db.php includes the code:
```
// Database connection
$pdo = new PDO('mysql:host=v1-db;dbname=db', 'root', 'root');

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

2. We'll be doing an API GET query with http://198.211.98.156/results/[job_id]. The results we expect should be formatted like this:
```
{
    "status": "completed",
    "result": {
        "_id": "65886d8cdef5aa77b8b3639a",
        "createdDate": "2023-12-24T17:42:36.392Z",
        "results": {
            "url": "https://decubing.com/felt-test/",

            // violations may be an empty array sometimes
            "violations": [
                {
                    "id": "landmark-contentinfo-is-top-level",
                    "impact": "moderate",
                    "tags": [
                        "cat.semantics",
                        "best-practice"
                    ],
                    "description": "Ensures the contentinfo landmark is at top level",
                    "help": "Contentinfo landmark should not be contained in another landmark",
                    "helpUrl": "https://dequeuniversity.com/rules/axe/4.8/landmark-contentinfo-is-top-level?application=axe-puppeteer",
                    "nodes": [
                        {
                            "any": [
                                {
                                    "id": "landmark-is-top-level",
                                    "data": {
                                        "role": null
                                    },
                                    "relatedNodes": [],
                                    "impact": "moderate",
                                    "message": "The null landmark is contained in another landmark."
                                }
                            ],
                            "all": [],
                            "none": [],
                            "impact": "moderate",
                            "html": "<footer class=\"c-jizsbi\">",
                            "failureSummary": "Fix any of the following:\n  The null landmark is contained in another landmark."
                        }
                    ]
                },
                {
                    "id": "landmark-unique",
                    "impact": "moderate",
                    "tags": [
                        "cat.semantics",
                        "best-practice"
                    ],
                    "help": "Ensures landmarks are unique",
                    "description": "Landmarks should have a unique role or role/label/title (i.e. accessible name) combination",
                    "helpUrl": "https://dequeuniversity.com/rules/axe/4.8/landmark-unique?application=axe-puppeteer",
                    "nodes": [
                        {
                            "any": [
                                {
                                    "id": "landmark-is-unique",
                                    "data": {
                                        "role": "banner",
                                        "accessibleText": null
                                    },
                                    "relatedNodes": [
                                        {
                                            "html": "<header class=\"c-bQDGwG c-bQDGwG-lohOMY-screen-embed\">",
                                            "target": [
                                                "iframe",
                                                "header"
                                            ]
                                        }
                                    ],
                                    "impact": "moderate",
                                    "message": "The landmark must have a unique aria-label, aria-labelledby, or title to make landmarks distinguishable"
                                }
                            ],
                            "all": [],
                            "none": [],
                            "impact": "moderate",
                            "html": "<header class=\"wp-block-template-part\">",
                            "target": [
                                "header"
                            ],
                            "failureSummary": "Fix any of the following:\n  The landmark must have a unique aria-label, aria-labelledby, or title to make landmarks distinguishable"
                        }
                    ]
                },
                {
                    "id": "link-name",
                    "impact": "serious",
                    "tags": [
                        "cat.name-role-value",
                        "wcag2a",
                        "wcag244",
                        "wcag412",
                        "section508",
                        "section508.22.a",
                        "TTv5",
                        "TT6.a",
                        "EN-301-549",
                        "EN-9.2.4.4",
                        "EN-9.4.1.2",
                        "ACT"
                    ],
                    "description": "Ensures links have discernible text",
                    "help": "Links must have discernible text",
                    "helpUrl": "https://dequeuniversity.com/rules/axe/4.8/link-name?application=axe-puppeteer",
                    "nodes": [
                        {
                            "any": [
                                {
                                    "id": "has-visible-text",
                                    "data": null,
                                    "relatedNodes": [],
                                    "impact": "serious",
                                    "message": "Element does not have text that is visible to screen readers"
                                },
                                {
                                    "id": "aria-label",
                                    "data": null,
                                    "relatedNodes": [],
                                    "impact": "serious",
                                    "message": "aria-label attribute does not exist or is empty"
                                },
                                {
                                    "id": "aria-labelledby",
                                    "data": null,
                                    "relatedNodes": [],
                                    "impact": "serious",
                                    "message": "aria-labelledby attribute does not exist, references elements that do not exist or references elements that are empty"
                                },
                                {
                                    "id": "non-empty-title",
                                    "data": {
                                        "messageKey": "noAttr"
                                    },
                                    "relatedNodes": [],
                                    "impact": "serious",
                                    "message": "Element has no title attribute"
                                }
                            ],
                            "all": [],
                            "none": [
                                {
                                    "id": "focusable-no-name",
                                    "data": null,
                                    "relatedNodes": [],
                                    "impact": "serious",
                                    "message": "Element is in tab order and does not have accessible text"
                                }
                            ],
                            "impact": "serious",
                            "html": "<a href=\"/\" target=\"_blank\" rel=\"noopener\">",
                            "target": [
                                "iframe",
                                "a[href=\"/\"]"
                            ],
                            "failureSummary": "Fix all of the following:\n  Element is in tab order and does not have accessible text\n\nFix any of the following:\n  Element does not have text that is visible to screen readers\n  aria-label attribute does not exist or is empty\n  aria-labelledby attribute does not exist, references elements that do not exist or references elements that are empty\n  Element has no title attribute"
                        }
                    ]
                }
            ]
        },
        "jobID": "55254"
    }
}
```

Let me know you understand then I'll ask you specific questions to help create our new file.

process_scan.php is going to compare the results of the api request with data in the "occurrences" table of our db, then process the results. The file will be triggered every two minutes from CRON process we've already setup. 

First, we're going verify the results and format them. Specifically, we need to make sure the "result" object contains "violations", which can contain an array of objects or be empty. Each object within that array, if there is an array, must contain the objects "id", "tags", "help", and "nodes". Nodes must include an html object and can include the objects "any", "all", or "none", which can include an array that includes the object "message".