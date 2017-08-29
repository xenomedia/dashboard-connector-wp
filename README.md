# Dashboard Connector WP #
**Contributors:** caromanel  
Donate link: 
**Tags:** jira, slack, xeno  
**Requires at least:** 3.7  
**Tested up to:** 4.8  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Dashboard Connector WP is a plugin that connects with Jira, Slack and the Xeno Rest API.

## Description ##

*   Adds two rest api end points:
	a ) site-info : with site core, theme and plugins information
	b ) slack-talk : send site site information to Slack
*   Creates a cron that will be running twice a day to check for updates. 
	If there are updates available will create a task in Jira and only for production environment.
*   Sends site information to Dashboard Connector WP
*   Creates an enviroment indicator


## Installation ##

1. Upload `dashboard-connector-wp` to the `/wp-content/plugins/` directory
2. Enter settings in dashboard-connector-wp admin page or edit wp-config.php file with the following paramethers:

/* Dashboard Connector WP settings */

// ** SLACK SETTINGS ** //

// Comma separated Slack Channels.

define( 'XDB_SLACK_CHANNELS', 'chanels' );

// Comma separated Slack usernames.

define( 'XDB_SLACK_NOTIFY', 'usernames' );

// Slack webhook ( after https://hooks.slack.com/services/ ).

define( 'XDB_SLACK_WEBHOOK', 'lastpartofwebhook' );


// ** JIRA SETTINGS ** //

// Default Jira transition ID.

define( 'XDB_JIRA_TRANSITION', 4 );

// Credentials: sername.

define( 'XDB_JIRA_USER', 'user' );

// Credentials: password.

define( 'XDB_JIRA_PWD', 'pwd' );

// Assignee username.

define( 'XDB_JIRA_ASSIGNEE', 'username' );

// Full URL.

define( 'XDB_JIRA_SERVER', 'jiraurl' );

// Project ID.

define( 'XDB_JIRA_PROJECT', 'project' );

// Comma separated labels.

define( 'XDB_JIRA_LABELS', 'labelsseparatedbycomma' );


// ** Dashboard Connector WP SETTINGS ** //

// Super secreat key.

define( 'XDB_REST_SECRET', 'mysupersecreat' );

// Credentials username.

define( 'XDB_USER', 'restuser' );

// Credentials password.

define( 'XDB_PWD', 'restpwd' );

// Site ID.

define( 'XDB_SITE_ID', 'siteid' );

// Client ID.

define( 'XDB_CLIENT_ID', 'clientid' );

// Dashboard Connector WP rest url.

define( 'XDB_URL', 'xenoresturl' );

// Environemnt: options are: dev, test and prod only.

define( 'XDB_ENV', 'devortestorprod' );

/* Dashboard Connector WP settings ENDS */


## Frequently Asked Questions ##



## Screenshots ##



## Changelog ##

### 1.0 ###
* Initial commit
