# SNDS plugin dla LMS

Outlook.com Smart Network Data Services

![](snds-welcome-page-example.png?raw=true)


## Requirements

Installed [LMS](https://lms.org.pl/) or [LMS-PLUS](https://lms-plus.org) (recommended).

## Installation

* Copy files to `<path-to-lms>/plugins/`
* Run `composer update` or `composer update --no-dev`
* Import table schema into DB from `snds.sql`
* Go to LMS website and activate it `Configuration => Plugins`

## Configuration

* Register on site: `https://sendersupport.olc.protection.outlook.com/snds/`
* Add networks to monitor `https://sendersupport.olc.protection.outlook.com/snds/addnetwork.aspx`
* `Enable automated access` on `https://sendersupport.olc.protection.outlook.com/snds/auto.aspx`
* Import default settings `configexport-snds-wartoscglobalna.ini`
* Go to `<path-to-lms>/?m=configlist` adjust the settings for yourself
* Replace `key` in `configexport-snds-wartoscglobalna.ini` with one that you have on website `https://sendersupport.olc.protection.outlook.com/snds/auto.aspx`
* Add job to cron to execute script `bin/lms-snds.php` for ex. at 12:00 once a day
