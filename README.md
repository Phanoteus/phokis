# Phokis

## Overview
Phokis is just the very basic beginnings of a backend PHP site structure based on PSR-15 middleware. It relies existentially on:

- The [auryn](https://github.com/rdlowrey/auryn) dependency injector
- The [FastRoute](https://github.com/nikic/FastRoute) routing library
- The [middleman](https://github.com/mindplay-dk/middleman) middleware dispatcher
- The [PSR-15](https://github.com/http-interop/http-middleware) work done by a bunch of folks

Some other libraries are very helpful, such as [monolog](https://github.com/Seldaek/monolog) and Zend's [Diactoros HTTP PSR-7 message implementation](https://github.com/zendframework/zend-diactoros), which is based on work by [Matthew Weier O'Phinney's](https://mwop.net/) [phly/http](https://github.com/phly/http) implementation.

## Installation
Such as it is. There's no Packagist package yet. This repo is it. If you want to take a look at Phokis or use it in a project, you can simply clone this repository:

```bash
$ git clone https://github.com/Phanoteus/phokis.git
```

And take advantage of repositories in your `composer.json` file to integrate it in to your project:

```js
"repositories": [
  {
    "type": "path",
    "url": "absolute\\local\\file\\path\\to\\phanoteus\\phokis\\"
  }
],
"require": {
  "phanoteus/phokis": "*"
},
```

And then run `composer update`.

## Usage

Assuming Composer has been installed and configured for your project, a simple index.php might look like this:

```php
use Phanoteus\Phokis\Server;

define('SITE_ROOT', __DIR__ . DIRECTORY_SEPARATOR);
require SITE_ROOT . 'vendor/autoload.php';

// Create and configure Injector.
// (See the sample config file for this purpose (config.example.php.)
$injector = new Auryn\Injector;
$config = require SITE_ROOT . 'src' . DIRECTORY_SEPARATOR . 'config.php';
$config->configure($injector, true);

// Have the $injector build the Request object (or build your Request object in some other way).
$request = $injector->make('Psr\Http\Message\ServerRequestInterface');

// You can use the $injector to make each middleware component and if a given component
// needs a factory or a logger, etc., in its constructor then the $injector will provision any dependencies.
// 
// So, build your middleware stack:
$stack = [
    $injector->make('Phanoteus\Phokis\Middleware\UriLogger'),
    $injector->make('Phanoteus\Phokis\Middleware\Router'),
    $injector->make('Phanoteus\Phokis\Middleware\RequestHandler') // Should be the last item in the stack.
];

// Make the middleman Dispatcher object with an on-the-fly $injector definition to
// pass in the $stack variable.
$dispatcher = $injector->make('mindplay\middleman\Dispatcher', [':stack' => $stack]);

// Process the middleware stack.
$response = $dispatcher->dispatch($request);

Server::emit($response);
```

## Credits and Acknowledgements

All of these people are the best. I seem to be resorting to their advice and guidance in one way or another all the time. If I think I vaguely understand something about PHP development, it's probably because I've heard it from one of them at one time or another. Or I've stolen their code. There are many others that I will try to remember.

- [Daniel Lowrey](https://github.com/rdlowrey), the primary developer of [auryn](https://github.com/rdlowrey/auryn).
- [Nikita Popov](https://github.com/nikic), the developer of [FastRoute](https://github.com/nikic/FastRoute).
- [Rasmus Schultz](https://github.com/mindplay-dk), the primary developer of [middleman](https://github.com/mindplay-dk/middleman).
- The engineering team at [When I Work](http://wheniwork.com). [The developers of the [Equip framework](https://github.com/equip/framework).]

- [Anthony Ferrera (ircmaxell)](http://blog.ircmaxell.com/)
- [Tom Butler](https://r.je/)
- [Matthew Weier O'Phinney](https://mwop.net/)
- [Woody Gilk](https://github.com/shadowhand)
- [Dan Ackroyd](https://github.com/Danack)
- [Oscar Otero](https://github.com/oscarotero)
- [Fabien Potencier](http://fabien.potencier.org/)
- [Patrick Louys](https://github.com/PatrickLouys)
- [Mārtiņš Tereško](https://stackoverflow.com/users/727208/tere%C5%A1ko)
- [Paul M. Jones](https://github.com/pmjones)
- [Josh Lockhart](https://github.com/codeguy)

## License
MIT License. See [LICENSE](LICENSE) for more information.

###### Coda
<small>Phanoteus (Φανοτέως) is from Phokis, according to Orestes.</small>
