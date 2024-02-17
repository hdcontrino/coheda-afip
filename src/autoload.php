<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'CohedaAfip\\';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0)
                return;

            $relative_class = substr($class, $len);
            $class_path = str_replace('\\', '/', $relative_class);
            $file = __DIR__ . "/$class_path.php";

            if (file_exists($file))
                require $file;
        });
    }
}

Autoloader::register();
