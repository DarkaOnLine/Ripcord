[![Total Downloads](https://poser.pugx.org/DarkaOnLine/Ripcord/downloads.svg)](https://packagist.org/packages/DarkaOnLine/Ripcord)
[![GuitHub Sponsor](https://img.shields.io/static/v1?label=Sponsor%20Ripcord&message=%E2%9D%A4&logo=GitHub)](https://github.com/sponsors/DarkaOnLine)

Ripcord - XML RPC client and server for PHP
==========

This packages is a copy of [https://code.google.com/p/ripcord/](https://code.google.com/p/ripcord/).


Installation
============

```php
    composer require darkaonline/ripcord
```

Requirements
============
Requires the PHP extension [XML-RPC](https://www.php.net/manual/en/book.xmlrpc.php).

For Laravel Users
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


## Support
[![GuitHub Sponsor](https://img.shields.io/static/v1?label=Sponsor%20Ripcord&message=%E2%9D%A4&logo=GitHub)](https://github.com/sponsors/DarkaOnLine)
