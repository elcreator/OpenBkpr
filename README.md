# OpenBkpr
Solution to automate bookkeeping tasks such as exporting transactions from bank API to CAMT054, JSON or CSV.

All banking systems have different output formats while integrations with many of bookkeeping software are not always
free if exists at all.

This solution aims to eliminate manual work of matching different CSV schemas, which often leads to errors.
It extracts all transactions for a given period and saves them in logically named files using the industry standard
ISO20022 (camt054.xml), CSV for wide support of accounting systems and JSON - for further integrations if needed.

## Supported data sources
Mercury bank, Stripe, PayPal

## Supported output formats
Camt.054.001.04, JSON, CSV

Example output with default settings and connected Stripe, Mercury and PayPal:

```
user@pc:~/openbkpr$ bin/ob.sh
Successfully saved to Stripe_2024-11-13-2025-01-13.camt054.xml!
Successfully saved to Stripe_2024-11-13-2025-01-13.json!
Successfully saved to Stripe_2024-11-13-2025-01-13.csv!
Successfully saved to Mercury_2024-01-01-2024-12-31.camt054.xml!
Successfully saved to Mercury_2024-01-01-2024-12-31.json!
Successfully saved to Mercury_2024-01-01-2024-12-31.csv!
Successfully saved to PayPal_2024-01-01-2024-12-31.camt054.xml!
Successfully saved to PayPal_2024-01-01-2024-12-31.json!
Successfully saved to PayPal_2024-01-01-2024-12-31.csv!
```

# Install
PHP 8.2+ should be installed.

Download and extract zip archive from GitHub, go to extracted ```openbkpr``` folder.

```chmod +x bin/*.sh``` on Linux to be able to run commands in bin folder.

Copy env.sample to .env

# Usage
### Windows
```bin/ob.cmd```
### Linux
```bin/ob.sh```

These scripts are just shortcuts for launching cli.php with PHP interpreter so further here in all docs ```cli.php -h``` means ```bin/ob.cmd -h``` or ```bin/ob.sh -h``` depending on your OS.

Start with -h option giving you help:

```
php "C:\projects\openbkpr\bin\cli.php" -h 
usage: cli.php <command> [<options>]

COMMANDS
  start          (or just run without any arguments) Starts execution assuming
                 everything OK in .env file
  paypal-token   Get PayPal token by providing Client Id (-i or --id) and Client
                 Secret (-s or --secret)

```

### All
```cli.php```
See output and adjust ```.env``` file if needed.

### PayPal

Business profile required. First command should be 

```cli.php paypal-token -i YOUR_CLIENT_TOKEN -s YOUR_SECRET ```

which gives you PAYPAL_TOKEN for pasting into your ```.env```. Also it shows you token expiration time after which this action should be repeated.

## Security warning

Use this tool only on trusted computer as it requires API tokens set in ```.env``` file.

Always restrict keys to reports only, don't use unrestricted keys!

Don't leave the ```.env``` file, or it's copy in places where it can be stolen by viruses or other people to prevent losing money.

Delete sensitive info from ```.env``` file if you are not planning to use it again in nearest time.

The program is provided "as is" without any guarantees.
