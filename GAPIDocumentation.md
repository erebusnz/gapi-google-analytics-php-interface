# Class Documentation #

This page describes the methods available when using the GAPI class.


## Methods ##

### Google Analytics Report Request ###

```
requestReportData($report_id, $dimensions, $metrics, $sort_metric=null, $filter=null, $start_date=null, $end_date=null, $start_index=1, $max_results=30)
```

| Attribute | Type (required/optional) | Description | Example |
|:----------|:-------------------------|:------------|:--------|
| $report\_id | String                   | ID of the report you wish to query | 1892302 |
| $dimensions | Array                    | Google Analytics dimensions | array('browser') |
| $metrics  | Array                    | Google Analytics metrics | array('pageviews') |
| $sort\_metric |  Array (optional)        | Dimension(s) or metric(s) to sort by. Ascending order default, precede option with '-' for descending order. Use array for combination of parameters. | array('-visits') |
| $filter   | String (optional)        | Filter logic for filtering results | see [using filter control](http://code.google.com/p/gapi-google-analytics-php-interface/wiki/UsingFilterControl) |
| $start\_date | String (optional)        | Start of reporting period YYYY-MM-DD | '2009-04-30' |
| $end\_date | String (optional)        | End of reporting period YYYY-MM-DD | '2009-06-30' |
| $start\_index | Int (optional)           | Start index of results | 1       |
| $max\_results | Int (optional)           | Max results returned. Max 1000 records | 30      |


### Google Analytics Account Request ###

```
requestAccountData($start_index=1, $max_results=20)
```

Int $start\_index OPTIONAL: Start index of results
Int $max\_results OPTIONAL: Max results returned


### Get Methods ###

```
getResults()
```

Returns: Array of gapiReportEntry objects.