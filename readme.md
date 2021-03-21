# goc-spending-laravel

Work-in-progress code to scrape, parse, and analyze contracting data from departments' [Proactive Disclosure](https://www.canada.ca/en/treasury-board-secretariat/services/reporting-government-spending/proactive-disclosure-department-agency.html) websites. Uses the [Laravel](https://laravel.com/docs/) framework.

## Dependencies

Requires an environment [that can run the latest version](https://laravel.com/docs/5.5#installation) of the Laravel Framework. The [Homestead](https://laravel.com/docs/5.5/homestead) environment works well.

## First-run setup

This repository is designed to scrape, parse, interpret, and import contracting data into a database. It operates in combination with several other repositories, which adds to the difficulty of the initial setup. Overall this repo is intended for one-time use, before the data is analyzed via the database.

Follow the steps below to install the various components (run these in your intended parent directory).

### 1\. Clone the three repositories:

The Laravel repo (this one) that runs scraping and parsing commands:
```
git clone git@github.com:GoC-Spending/goc-spending-laravel.git
```

The vendor normalization data:
```
git clone git@github.com:GoC-Spending/goc-spending-vendors.git
```

And, the set of scraped and parsed data from departmental websites. Note that this repo is several GB in size and will take a while to download:
```
git clone git@github.com:GoC-Spending/goc-spending-data.git
```

### 2\. Install dependencies, create a .env file, and run database migrations

Install the Laravel project dependencies using [Composer](https://getcomposer.org/).

_Note, because this project uses an older version of the Laravel framework, [you should use version 1 of Composer](https://github.com/composer/composer/issues/9340#issuecomment-716210369)._

```
cd goc-spending-laravel
composer install
```

Create a copy of `.env.example` and name it `.env`. This will allow you to customize any settings on a per-installation basis.

```
cp .env.example .env
```

**Update the database settings in the new `.env` file** to match your intended database (e.g. PostgreSQL or MySQL hostname, username, password, and database).

Then, run the artisan migrate command to set up your database tables. This is a good way to test if your `.env` database settings are correct.

```
php artisan migrate
```

### 3\. Download the latest CSV dataset from open.canada.ca

Because the [proactive disclosure of contracts CSV dataset](https://open.canada.ca/data/en/dataset/d8f85d91-7dec-4fd1-8055-483b77225d8b) is larger than the 100MB GitHub file size limit, the repository includes a helper function to download it directly. 

This requires write access to the parent folder (above the `goc-spending-laravel` folder). It may take a few minutes to download the file.

```
php artisan csv:download
```

## Scraping and parsing

These were the original use-cases for this repository. As of 2019, most departmental proactive disclosure pages have either already been added to the [`goc-spending-data` repository](https://github.com/GoC-Spending/goc-spending-data) or are no longer available online. 

Previously, the two functions below were used to scrape and parse data. 

**Unless you've added a new DepartmentHandler class, you should skip to [Importing data into the database](#importing-data-into-the-database).**

### 1\. Scraping a department's proactive disclosure webpage.

Department-specific scrapers are included in the [DepartmentHandlers](https://github.com/GoC-Spending/goc-spending-laravel/tree/master/app/DepartmentHandlers) folder.

To retrieve data from a particular department's proactive disclosure pages, use the departmental acronym listed in `DepartmentHandlers` with the Artisan `fetch` command.

For example, for `ec` (Environment Canada):

```
php artisan department:fetch ec
```

### 2\. Parsing downloaded HTML from a department

The fetch function scrapes individual contract or amendment pages from the specified department, and stores the HTML in the `goc-spending-data` folder (in the parent directory).

Then, it can be parsed into structured JSON data using the `parse` command, per department. This identifies table cells etc. in the HTML that match each standard proactive disclosure field (vendor name, total value, amended value, etc.). The fields available vary somewhat from department to department.

```
php artisan department:parse ec
```

### 3\. Scraping and parsing in one go

This is an alternative to the two commands above, to scrape pages for a department and then immediately parse them into structured JSON data: 

```
php artisan department:parse ec
```

## Importing data into the database

To import data from the structured JSON data files into a database, use the following command. This could take a long time (up to several hours on slow machines). 

**See [Tips](#tips) below for ways to run these commands on intermittent network connections.**

You can monitor its progress by checking the total number of entries in the `l_contracts` table in your database.

```
php artisan department:importall
```

To import data from the open.canada.ca dataset CSV into the database, use the command below. You should do this after importing the structured JSON data files above.

```
php artisan csv:import
```

## Update automatically-generated metadata

The repository includes a number of functions to analyze and clean up the contracting data. These functions are located in the [`DbOps` class](https://github.com/GoC-Spending/goc-spending-laravel/blob/master/app/DbOps.php). This includes,

- finding duplicate entries `findDuplicates`
- finding amendments to previous contracts, and grouping them together `findAmendments`
- determining the effective (normalized year-over-year) values of amendment groups `updateEffectiveAmendmentValues`
- determining the effective values (year-over-year) of regular, non-amended contracts `updateEffectiveRegularValues`

The results of these metadata operations are stored in the table columns starting with `gen_`, in order to clearly distinguish them from original source data.

Many of these operations could be more efficient if done outside of PHP (for example in R or Python libraries designed for data analysis). People analyzing this data in the future could recalculate these by other means, rather than using the `gen_` results, if preferred. 

To run all of the automatic metadata functions, use the following command:

```
php artisan department:allmetadata
```

This can be a slow and unpredictable command, especially on small virtual machines. It keeps track of which departments have been completed, in `storage/metadata_status.json`.

The command above can be re-run if it crashes before finishing every department.

**Alternatively**, to re-run the metadata generation all departments (including those that have already been completed), use the following flag:

```
php artisan department:allmetadata --reset
```

## Data "export" consolidation functions

In order to more easily analyze the contracting data, there are currently two "export" commands that do calculations and then add the results to a subsequent database table.

### Include all rows with a specified fiscal year, minus duplicates:

This skips duplicate entries, and any rows without a specified fiscal year, and adds all other rows to the `exports_v1` table:

```
php artisan export:v1
```

### Create a year-by-year breakdown of contracts and their amendments

This takes the results of the command above (in the `exports_v1` table) and creates individual, non-overlapping rows where each row is for a given contract (and its amendments) for a particular span of years (e.g. 2007 to 2009), with the effective value (total and per-year) included.

This lets you do more specific time-based analyses that include contracts and their amendments.

To generate the table, run:

```
php artisan export:v2
```

The `export:v1` function above needs to be run first.

More export/calculation functions could be added in the future, but from this point, doing calculations through SQL queries or through downloading the resulting data into another analysis tool is likely more effective.

## Tips

Because many of the operations above could take several hours, making sure that they aren't interrupted halfway through is useful.

If you're running these on a virtual machine (recommended), you can use a tool like [Mosh](https://mosh.org/) to connect more reliably. On regular SSH, if your connection is dropped, it's likely to cancel the most recent command you started.

You can also run these commands in background mode, to continue even when your terminal connection is closed. Be careful not to start another version of the same command while one is still running (the functions above don't block or check for existing processes).

You can do this and log the results to a local file, to review the results afterwards. For example,

```
php artisan csv:download > _results.log 2>&1 &
```

This saves the output to the `_results.log` file (in the working directory). You can `tail -f _results.log` to see results in real time, once the command above is started. Change the `csv:download` section above to run different commands in background mode.

## About

This repository (and the related [GoC-spending repositories](https://github.com/GoC-Spending)) are part of an [Ottawa Civic Tech](http://ottawacivictech.ca/) project. Get in touch by joining our [Slack channel](https://yowct-invite-bot.herokuapp.com/) (`#big_little_contractin`), or by reaching out to any of the contributors.

This is a volunteer project and is not affiliated with the Government of Canada.

## License

This code, and the Laravel framework on which it is based, is open source software licensed under the [MIT license](http://opensource.org/licenses/MIT). 

