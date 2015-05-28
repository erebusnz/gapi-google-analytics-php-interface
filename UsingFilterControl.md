# Using the GAPI filter control #

The GAPI filter control is designed to making filtering with the Google Analytics API easier - through the use of normal PHP operators for AND and OR, and the translation of metrics and dimensions to the correct Google Analytics namespaced versions.

The Google Analytics API has a small amount of documentation on using the Google Analytics API filter control at the [Google Analytics API reference](http://code.google.com/apis/analytics/docs/gdata/gdataReference.html#filtering).

The most frustrating aspect of the Google Analytics API filter control is that Google hasn't yet offered any operators to specify precedence. Where we would normally use ( and ) in PHP, we can't do anything in our filters here. To allow the sensible use of OR in the filter control, they have given the OR operator a higher precedence than AND.

For example, where we would normally write:

country == 'United States' && ( browser == 'Firefox' || browser == 'Chrome')

This would be represented in a GAPI filter string by:

country == 'United States' && browser == 'Firefox' || browser == 'Chrome'

As with the metric and dimensions selection for GAPI requests, you should not include the 'ga:' namespace that some Google Analytics APIs require.

## Valid Operators for filtering ##

As seen on the [Google Analytics API reference](http://code.google.com/apis/analytics/docs/gdata/gdataReference.html#filtering), there are six operators for metrics and six operators for dimensions. The GAPI interface allows you to use all six operators. GAPI also gives you '&&' and '||' operators for AND and OR.

### GAPI Operators ###
| Operator | Description |
|:---------|:------------|
| &&       | And         |
| `|``|`   | Or          |

### Metric Filters ###
| Operator | Description |
|:---------|:------------|
| ==       | Equals      |
| !=       | Not equal   |
| >        | Greater than |
| <        | Less than   |
| >=       | Greater than or equal to |
| <=       | Less than or equal to |

### Dimension Filters ###
| Operator | Description |
|:---------|:------------|
| ==       | Exact match |
| !=       | Does not match |
| =~       | Matches a regular expression |
| !~       | Does not match regular expression |
| =@       | Contains substring |
| !@       | Does not contain substring |

## Examples ##

## Simple examples ##

Select results where country dimesion matches 'United States', browser dimension matches 'Firefox' and browserVersion dimension does not match '3.0.10'.

```
$filter = 'country == United States && browser == Firefox && browserVersion != 3.0.10';
```

Select results where country dimension matches 'United States' and visits metric is greater than 30.

```
$filter = 'country == United States && visits > 30';
```

### Regular expression examples ###


Select results where country dimesion matches 'United States', browser dimension matches 'Firefox' and browserVersion matches regular expression '^3\.' (starts with '3.').

```
$filter = 'country == United States && browser = Firefox && browserVersion =~ ^3\.';
```