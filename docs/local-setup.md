# Local Setup

## DDEV

The SNAC server uses DDEV for local development.

Follow the [getting started guide](https://ddev.com/get-started/) to install DDEV on your machine.

### Running the server

Once DDEV is installed, run the following command in the root of this project.

```sh
ddev start
```

### Installing dependencies

Once the server is running, run the following command to install the dependecies with composer.

```sh
ddev composer install
```

### Stopping the server

Run the following command to stop your project.

```sh
ddev stop
```

### Importing an existing database

Normally you'll start working with an existing database dump.

For a more detailed explanation on how to download an existing database dump, [see here](./database.md).

Due to the size of the SNAC database and the dump format used you will be unable to use the `ddev import-db` command. Instead you will want to use a database client, see [the DDEV docs](https://ddev.readthedocs.io/en/stable/users/usage/database-management/#database-guis) for GUIs.

The database is also exposed on localhost:54320 if you would prefer to use a client not supported by DDEV

### Rebuilding elasticsearch indices

In order to have search work correctly after importing a database you will need to rebuild the elasticsearch indices.

Run the following commands to rebuild the indices
```sh
ddev ssh
cd scripts
php rebuild_elastic.php nowiki
php rebuild_resource_elastic.php
php rebuild_browse_index.php
```
