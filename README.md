# Edge conf

The website.

## Development

To set up a dev environment the following prerequisites must be installed on your system:

* MySQL (tested with 5.1.71)
* PHP (tested with 5.3.3)
* Composer (tested with 2014-01-14 build)
* SASS (tested with 3.2.13)
* A web server (Apache or NGINX would both be fine)

Once those are installed, perform the following steps to set up your environment:

1. Clone the repo
2. Copy the `app/config.ini.dist` file to `app/config.ini` and fill in the relevant data (ask me)
3. In the root of the repo run `composer install` to install dependencies
4. Set up a virtual host to serve from `/public` and redirect all requests that can't be resolved by the filesystem to `/public/index.php` (on Apache, you need `RewriteRule`.  On NGINX, `try_files`)
5. Create a database on the MySQL server you configured, restoring a backup of current data (ask me)
6. Fire up your browser and you're in business.

There is a further build step for the SASS source files, so if you've installed the SASS gem you can compile SASS changes with:

    sass app/sass/app.scss > public/css/app.css
