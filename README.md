# OpenBkpr
Solution to automate bookkeeping tasks such us transaction export from bank API to CAMT054, CAMT053, JSON or CSV
## Supported data sources
Mercury bank, Stripe
## Supported output formats
Camt.054.001.04, JSON, CSV

# Install
PHP 8.2+ should be installed.
```chmod +x bin/*.sh``` on Linux to be able to run commands in bin folder.
Copy env.sample to .env

# Usage
## Windows
```bin/camt054.cmd```
## Linux
```bin/camt054.sh```

See output and adjust .env if needed
