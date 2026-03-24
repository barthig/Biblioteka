<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

if (!class_exists(AppReference::class, false)) {
    /**
     * Compatibility shim for environments where the installed
     * symfony/dependency-injection package does not expose AppReference.
     *
     * It only satisfies PhpFileLoader's class_alias(AppReference::class, App::class)
     * bootstrap path. The project does not rely on PHP DI config files, so
     * invoking App::config() should be treated as unsupported here.
     */
    final class AppReference
    {
        public static function config(): never
        {
            throw new \LogicException('App::config() is not supported in this project configuration.');
        }
    }
}
