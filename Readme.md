[![Total Downloads](https://poser.pugx.org/DarkaOnLine/Ripcord/downloads.svg)](https://packagist.org/packages/DarkaOnLine/Ripcord)

Ripcord - XML RPC client and server for PHP
==========

This packages is a copy of [https://code.google.com/p/ripcord/](https://code.google.com/p/ripcord/).


Installation
============

```php
    composer require darkaonline/ripcord
```

For Laravel 5 Users
============

- Open your `AppServiceProvider` (located in `app/Providers`) and add this line in `register` function
```php
    $this->app->register(\Ripcord\Providers\Laravel\ServiceProvider::class);
```
- Run `ripcord:publish` to publish configs (`config/ripcord.php`) or just copy `ripcord.php` file from `vendor/darkaonline/ripcord/src/Ripcord/Prividers/Laravel/config` and paste to `config` folder

## Extending

Just extend `Ripcord` class and all your coinfg and basic connection will be done for you automaticly

```php
  namespace Foo\Bar;
  
  use Ripcord\Providers\Laravel\Ripcord;
  
  class Provider extends Ripcord
  {
    ...
  }
```
