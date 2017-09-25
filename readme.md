# goc-spending-laravel

Work-in-progress code to scrape and then parse contracting data from departments' Proactive Disclosure website. Uses the [Laravel](https://laravel.com/docs/) framework. Based on previous work in [goc-spending-mini](https://github.com/GoC-Spending/goc-spending-mini).

## Dependencies

Requires an environment [that can run the latest version](https://laravel.com/docs/5.5#installation) of the Laravel Framework. The [Homestead](https://laravel.com/docs/5.5/homestead) environment works well.

## First-run setup

1. Clone the repository
2. Go to the new folder, and run `composer install`. This will download any Laravel and project-specific dependencies.
3. Create a copy of `.env.example` and name it `.env`. This will allow you to customize any settings on a per-installation basis.
4. ???
5. Profit!

We have a set of Artisan commands available, under the `department` namespace. (Run `php artisan help department:` to get a
complete list.) To run them, you’ll need a department acronym—you can see the full list of supported departments by checking
the `app/DepartmentHandlers/` folder. Example usage, for Environment Canada:

```
php artisan department:fetch ec
php artisan department:parse ec
```

Or, running the fetch and parse in one go:

```
php artisan department:run ec
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
