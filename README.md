# PhpAPI

Create a API for your PHP project in minutes. Simple add an annotation to yours methods and "voilá": it's done.

## Usage

```php
/**
 * @Api("my/route")
 * No @HttpMethod annotation, accepts all HTTP kinds of request methods
 */
public function myRoute() {
    // code
}

/**
 * @Api("route/with/$var")
 * @HttpMethod("PUT") // will accept only PUT requests
 */
public function routeWith($var) {
    // code
}

/**
 * @Api("another/$id/maybe/$var")
 * @HttpMethod("GET,POST") // will accept only GET and POST requests
 */
public function routeAnother($id, $var) {
    // code
}
```

As you can see, it uses a Symfony-like router to find the right code to execute. Moreover, it suports the use of patterns so some parts of request can be variables!

Just add a` $` inside **@Api** value to make it act as a method parameter.

## Installation

This library can be found on [Packagist](https://packagist.org/packages/ifroariquemes/php-api). We endorse that everything will work fine if you install this through `composer`.

Add in your `composer.json`:
```json
{
    "require": {
        "ifroariquemes": "dev-master"
    }
}
```
or in your bash
```sh
$ composer require natanaelsimoes/zeus-framework
```

From any point, make available the following code to start:

```php
require './vendor/autoload.php';
\PhpApi\PhpApi::start('src'); // your source code directory
```

If this script is available at http://example.com/api/v1/ (suposing you already have a router working previously) then the API router will understand things starting there:
- http://example.com/api/v1/my/route
- http://example.com/api/v1/route/with/me
- http://example.com/api/v1/another/way/maybe/works

If you are not using a router, but created a new directory to keep our code, remember to enable `mod_rewrite` and have a file like this its root to get things done:

```apache
AddType application/x-httpd-php .php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L]
</IfModule>
<Files "keys.json">
Order Allow,Deny
Deny from all
</Files>
```

This lib also uses a token system. You can activate it by creating a file named `keys.json` at API/project root folder:

```json
{
    "SystemName": "token123456"
}
```

The client will use this token to communicate with the API by adding its value to the key `X-API-KEY` within the HTTP request header.

## Docs & Contribution
You can also check the [docs/](docs/) for futher information about the classes and stuff. All codes are well commented so fell free to go deep and help us get this even better.

## Profit!