# goc-spending-laravel

Work-in-progress code to scrape and then parse contracting data from departments' Proactive Disclosure website. Uses the [Laravel](https://laravel.com/docs/) framework. Based on previous work in [goc-spending-mini](https://github.com/GoC-Spending/goc-spending-mini).

## Dependencies

Requires an environment [that can run the latest version](https://laravel.com/docs/5.5#installation) of the Laravel Framework. The [Homestead](https://laravel.com/docs/5.5/homestead) environment works well.

## First-run setup

1. Clone the repository
2. Go to the new folder, and run `composer update`. This will download any Laravel and project-specific dependencies.
3. Create a copy of `.env.example` and name it `.env`. This will allow you to customize any settings on a per-installation basis.
4. ???
5. Profit!

At the moment, all of the code is run manually via the `php artisan tinker` command-line tool. 

For example, to run the Environment Canada parser:

```
$department = new App\DepartmentHandlers\EcHandler;
$department->fetch();
$department->parse();
```

More usage details to follow.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
