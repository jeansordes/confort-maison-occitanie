## Getting started
### Using Docker
```docker compose up -d```
This will create a container with PHP 7.4, Xdebug (a PHP debugger), Composer (a php dependencies manager) and PostgreSQL 12 (the database) and will init the project to make if fully fonctionnal.

The default user is `admin_cmo@yopmail.com`, the password is `admin`

## How to import / modify the database structure
⚠️ This is a destructive action, it will wipe clean the database ⚠️
You have 3 folders for the database structure, they are located in the `app/db/sql` folder. If you want to import the data from prodose_v1, you need to put the DB dump in the `/app/db/sql/2-seed`

Then, if you run the project on Docker, run the following commands :
```
docker compose down --volumes && docker compose up --build --force-recreate --detach
```