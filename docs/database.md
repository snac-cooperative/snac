# About

When first getting started or occasionally while working on SNAC, you may need to update your local
database to match what is currently running on **dev**.

# Downloading the dump

In order to download the database dump you will need access to the snac dev server, and you will
need to be connected to the UVA VPN.

Download the latest database backup using scp e.g.

```sh
scp snac-dev:/data/backups/{timestamp}_development.dump .ddev/db_snapshots/
```
