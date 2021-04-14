### A very simple directory of web sites (used in the Yggdrasil network)

Dependencies: sqlite3, parallel, php-sqlite3, php-gd

How to start using it:
 * Install dependencies (`sudo apt install sqlite3 parallel php-sqlite3 php-gd`); Note that after installing GNU Parallel, you need to run it for the first time with the `--citation` parameter to hide the extra output
 * Copy the contents of this repository to your web server
 * Inside the directory where the contents of this repository are located, create a database named `database.db` using this script: [database_schema.sql](database_schema.sql) (or copy this database: [database.db](db_example/database.db))
 * Configure the web server. [Here](nginx/sites_dir.conf) is an example of the nginx web server configuration file
 * Give the web server write access to this directory and and write access to the `database.db` file
 * Add a cron job: `0 */1 * * * /path/to/sites_dir/checkavailability.sh >/dev/null 2>&1` (hourly site availability check and html files regeneration)
 * The permissions for the files `site/index.html*`, `site/categories.html*` must be that they can be changed from both the cron job and the web server 
 * If you want to receive notifications about changes in the list of sites in telegram, fill in the parameters in the file `php-backend/ygg_telegram_notify.php`

These scripts are currently used on the [Yggdrasil](https://yggdrasil-network.github.io/) network at this address: http://[300:529f:150c:eafe::1]/
