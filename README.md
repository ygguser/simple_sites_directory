### A very simple directory of web sites (used in the Yggdrasil network)

Dependencies: curl, sqlite3, parallel, php-sqlite3, php-gd, php-curl

How to start using it:
 * Install dependencies (`sudo apt install curl sqlite3 parallel dnsutils php-sqlite3 php-gd php-curl`); Note that after installing GNU Parallel, you need to run it for the first time with the `--citation` parameter to hide the extra output
 * Copy the contents of this repository to your web server
 * Inside the directory where the contents of this repository are located, create a database named `database.db` using this script: [database_schema.sql](database_schema.sql) (or copy this database: [database.db](db_example/database.db))
 * Configure the web server. [Here](nginx/sites_dir.conf) is an example of the nginx web server configuration file
 * Give the web server write access to this directory and write access to the `database.db` file
 * Add a cron job: `0 */1 * * * /path/to/sites_dir/checkavailability.sh >/dev/null 2>&1` (hourly site availability check and html files regeneration)
 * The permissions for the files `site/index.html*`, `site/categories.html*` must be that they can be changed from both the cron job and the web server 
 * If you want to receive notifications about changes in the list of sites in [telegram](https://telegram.org/), fill in the parameters in the file `php-backend/ygg_telegram_notify.php`

To manually manage records in the database it is convenient to use this php-script: https://www.phpliteadmin.org/ (php-mbstring is required).

[Here](nginx/sites_dir_psqla.conf) an example of nginx configuration file for working with phpLiteAdmin is provided (see the comments in this file).

Configure these parameters in the 'phpliteadmin.php' file like this:
```
$directory = false;

$databases = array(
        array(
                'path'=> '../../database.db',
                'name'=> 'Database'
        ),
);
```

These scripts are currently used on the [Yggdrasil](https://yggdrasil-network.github.io/) network at this address: http://[21e:a51c:885b:7db0:166e:927:98cd:d186]/
