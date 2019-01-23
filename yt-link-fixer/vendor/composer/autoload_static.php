<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit586b97fe8fdc1f70ddbca5f8e5075714
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '70656711eaeff0eecdc3608748403524' => __DIR__ . '/..' . '/antalaron/mb-similar-text/src/bootstrap.php',
        '89ff252b349d4d088742a09c25f5dd74' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/plugin-update-checker.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'A' => 
        array (
            'Antalaron\\MbSimilarText\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Antalaron\\MbSimilarText\\' => 
        array (
            0 => __DIR__ . '/..' . '/antalaron/mb-similar-text/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit586b97fe8fdc1f70ddbca5f8e5075714::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit586b97fe8fdc1f70ddbca5f8e5075714::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
