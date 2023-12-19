Create PHP that does the following:
1. Run an API request to http://198.211.98.156/generate/sitemapurl with a json body that includes { "url" : "[URL_GOES_HERE]"}. [URL_GOES_HERE] will be replaced by a variable that's passed to the file when it's run in command line.  Results should look like this:

```
[{"JobID":"23082","URL":"https://www.berklee.edu/berklee-now"},{"JobID":"22952","URL":"https://www.berklee.edu/berklee-now/read"},{"JobID":"22953","URL":"https://www.berklee.edu/berklee-now/watch"}]
```

2. Save the results of that request to the "active_scans" db table. Each JobID should be saved into the scan_job_id column of a new row and the URL should be saved.

3. Intiate the scan function.

Here is what the scan function should do:

1. query the active_scans table for the lowest job_id.

2. Use that job id in the the API request `http://198.211.98.156/results/[jobID]` to get results of a single Job. You'll get back one of four results:
    Result 1:
    ```
    {
        "status": "waiting",
        "result": null
    }
    ```
    Result 2:
    ```
    {
        "status": "active",
        "result": null
    }
    ```
    Result 3:
    ```
    {
        "status": "failed",
        "result": null
    }
    ```
    Result 4:
    ```
    {
        "status": "completed",
        "result": {}
    }
    ```
3. If the status is waiting or active, kill the process and use a cron job to run the scan function again in two minutes. If the status is "completed", you the API should have returned something like this: 
```
{
  "status": "completed",
  "related_url": "https://www.berklee.edu/berklee-now",
  "result": {
    "_id": "65806ea911dc07f9c494008e",
    "createdDate": "2023-12-18T16:09:13.643Z",
    "results": {
      "violations": [
        {
          "id": "color-contrast",
          "tags": [
            "cat.color",
            "wcag2aa",
            "wcag143",
          ],
          "description": "Ensures the contrast between foreground and background colors meets WCAG 2 AA minimum contrast ratio thresholds",
          "help": "Elements must meet minimum color contrast ratio thresholds",
          "nodes": [
            {
              "html": "<a href=\"/telling-your-berklee-story/services/editorial\" rel=\"prev\" title=\"Go to previous page\"><b>‹</b> Editorial</a>",
              "failureSummary": "Fix any of the following:\n  Element has insufficient color contrast of 4.23 (foreground color: #ee243c, background color: #ffffff, font size: 10.8pt (14.4571px), font weight: normal). Expected contrast ratio of 4.5:1"
            },
            {
              "html": "<a href=\"/telling-your-berklee-story/services\" title=\"Go to parent page\">Up</a>",
              "failureSummary": "Fix any of the following:\n  Element has insufficient color contrast of 4.23 (foreground color: #ee243c, background color: #ffffff, font size: 10.8pt (14.4571px), font weight: normal). Expected contrast ratio of 4.5:1"
            }
          ]
        },
        {
          "id": "heading-order",
          "tags": [
            "cat.semantics",
            "best-practice"
          ],
          "description": "Ensures the order of headings is semantically correct",
          "help": "Heading levels should only increase by one",
          "nodes": [
            {
              "html": "<h5>Stay Connected</h5>",
              "failureSummary": "Fix any of the following:\n  Heading order invalid"
            }
          ]
        }
      ]
    },
    "jobID": "23082"
  }
}
```

4. Reform any data in the "violations" array of the results into a new variable called occurrences. $occurrences should look reformat violations like this:
<?php
$occurrences = array(

    // Example data
    array(
        // message_title is take from the violation "help" data 
        'message_title' => 'Elements must meet minimum color contrast ratio thresholds',
        // message_body is take from the violation "description" data 
        'message_body' => 'Ensures the contrast between foreground and background colors meets WCAG 2 AA minimum contrast ratio thresholds', 
        // tags are taken from the violation "tags" data 
        'tags' => [
            "cat.color",
            "wcag2aa",
            "wcag143",
        ],
        // code is taken from the violation "html" data 
        'occurrence_code_snippet' => '<a href=\"/telling-your-berklee-story/services\" title=\"Go to parent page\">Up</a>',

        // this is gotten from the related_url of the API result
        'related_url' => 'https://www.berklee.edu/berklee-now',

        // Property is set elsewhere
        'property_id' => 1,

        // We'll use these items later
        'occurrence_id' => '',
        'occurrence_message_id' => '',
        'occurrence_property_id' => '',
        'occurrence_page_id' => '',
        'occurrence_tag_ids' => '',
        'occurrence_source' => 'Equalify Scan',
        
    )
    // ... Each array within "nodes" should get its own nested array. We would expect 3 different arrays within the violatioins array, one for each node that exists within each violation.
)
?>

5. For each occurrence, look into the messages table and see if any db entry has matching  message_title and message_body. If it does, update the occurrence_message_id data with the id of the message. If it doesn't, add the message_title and message_body to a new row, then add the id of the new row to 'occurrence_message_id'. 

6. For each occurrence, look into the tags table and see if any db entry has matching  tag_name of the tags. If it does, update the occurrence_tag_ids data with the ids of the message. If it doesn't, add the tag_name to a new row with a sanitized version of tag_name in tag_slug, then add the id of the new row to 'occurrence_tag_ids'.

8. For each occurrence, look into the pages table and see if any db entry has matching page_url to the related_url data. If it does, update the occurrence_page_id data with the id of the page. If it doesn't, add the related_url in the page_url column of a new row, then add the id of the new row to 'occurrence_page_id'. 

9. Once steps 5-8 have been completed for each occurrence, add all the occurrences to the queued_occurrences table.

... Next we'll handle comparing the occurrences and adding content to the updates table.