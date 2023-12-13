# Assumptions
- [ ] Will will be able to query by data in an array, nested in a column with MySQL?

# UX Updates
- Remove numbers from status table
- Status by time chart is the only thing that is filtered by time. Move time range to chart.
- Notice table only shows occurances.
- Change Page secton "Equalifed" percentage to "Occurances" count.

# Tables

## Status Updates

### Data Flow
A row is added when the status of a notice changes or is created.

#### Example
We added new notice with the "active" status. A scan ran and that notice turned equalified. That notice would be marked by two entries in the Status Updates Table.

### Columns
- notice_id
- timestamp
- status_id
- tag_ids
- property_id
- page_id

### Sample Function
To populate are chart that's filtered to status updates related to "example.com" from Jan 3 - Feb 3, 2023:

```
<?php
$tags = array(4,3,2);
$related_pages = array(85,33,22);
$start_time = mm:dd:yy;
$end_time = mm:dd:yy;
get_log_by_time($tags, $related_pages, $start_time, $end_time){
    
    // Query

    // Return as array
}
?>
```

## Messages
There are a finite number of messages. These messages shouldn't be confused with notices, which are the occurances of a message on a page.

### Requirements
- Must be able to be filtered by tag, page, status, and property.

### Columns
- message_id
- message

## Occurences
Everytime a message is associated with a page, it creates a new occurence.

### Columns
- occurance_id
- message_id
- tag_ids
- property_id
- status_id
- page_id

### Assumptions
- We'll be able to return count and message_id

## Tags
A finate number of tags are related to equalify items. This table is used to get a tag's name.

### Requirements
- Must return a name when querying an id.

### Columns
- tag_id
- tag_name

### Pages
Every unique URL scanned by Equalify gets a page. This table is used to get a page's url.

### Requirements
- Must return a url when querying an id.

### Columns
- page_id
- page_url


