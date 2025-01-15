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

See output and adjust ```.env``` file if needed.

## Security warning

Use this tool only on trusted computer as it requires API tokens set in ```.env``` file.

Always restrict keys to reports only, don't use unrestricted keys!

Don't leave the ```.env``` file, or it's copy in places where it can be stolen by viruses or other people to prevent losing money.

Delete sensitive info from ```.env``` file if you are not planning to use it again in nearest time.

The program is provided "as is" without any guarantees.

Example output with default settings and connected Stripe and Mercury:

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
