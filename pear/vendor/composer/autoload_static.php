<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1
{
    public static $files = array (
        '383eaff206634a77a1be54e64e6459c7' => __DIR__ . '/..' . '/sabre/uri/lib/functions.php',
        '2b9d0f43f9552984cfa82fee95491826' => __DIR__ . '/..' . '/sabre/event/lib/coroutine.php',
        'd81bab31d3feb45bfe2f283ea3c8fdf7' => __DIR__ . '/..' . '/sabre/event/lib/Loop/functions.php',
        'a1cce3d26cc15c00fcd0b3354bd72c88' => __DIR__ . '/..' . '/sabre/event/lib/Promise/functions.php',
        '3569eecfeed3bcf0bad3c998a494ecb8' => __DIR__ . '/..' . '/sabre/xml/lib/Deserializer/functions.php',
        '93aa591bc4ca510c520999e34229ee79' => __DIR__ . '/..' . '/sabre/xml/lib/Serializer/functions.php',
        'b1a58bc77bedec0951c59783887c6c69' => __DIR__ . '/..' . '/alecrabbit/php-cli-tools/src/bootstrap.php',
        '253c157292f75eb38082b5acb06f3f01' => __DIR__ . '/..' . '/nikic/fast-route/src/functions.php',
        'ebdb698ed4152ae445614b69b5e4bb6a' => __DIR__ . '/..' . '/sabre/http/lib/functions.php',
        'dee5f8062ae555c40ebb714ef2df156c' => __DIR__ . '/..' . '/daverandom/resume/src/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Slim\\' => 5,
            'Sabre\\Xml\\' => 10,
            'Sabre\\VObject\\' => 14,
            'Sabre\\Uri\\' => 10,
            'Sabre\\HTTP\\' => 11,
            'Sabre\\Event\\' => 12,
            'Sabre\\DAV\\' => 10,
            'Sabre\\DAVACL\\' => 13,
            'Sabre\\CardDAV\\' => 14,
            'Sabre\\CalDAV\\' => 13,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Container\\' => 14,
        ),
        'F' => 
        array (
            'FastRoute\\' => 10,
        ),
        'D' => 
        array (
            'DaveRandom\\Resume\\' => 18,
        ),
        'A' => 
        array (
            'AlecRabbit\\Cli\\' => 15,
            'AlecRabbit\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Slim\\' => 
        array (
            0 => __DIR__ . '/..' . '/slim/slim/Slim',
        ),
        'Sabre\\Xml\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/xml/lib',
        ),
        'Sabre\\VObject\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/vobject/lib',
        ),
        'Sabre\\Uri\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/uri/lib',
        ),
        'Sabre\\HTTP\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/http/lib',
        ),
        'Sabre\\Event\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/event/lib',
        ),
        'Sabre\\DAV\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/dav/lib/DAV',
        ),
        'Sabre\\DAVACL\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/dav/lib/DAVACL',
        ),
        'Sabre\\CardDAV\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/dav/lib/CardDAV',
        ),
        'Sabre\\CalDAV\\' => 
        array (
            0 => __DIR__ . '/..' . '/sabre/dav/lib/CalDAV',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Psr\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/container/src',
        ),
        'FastRoute\\' => 
        array (
            0 => __DIR__ . '/..' . '/nikic/fast-route/src',
        ),
        'DaveRandom\\Resume\\' => 
        array (
            0 => __DIR__ . '/..' . '/daverandom/resume/src',
        ),
        'AlecRabbit\\Cli\\' => 
        array (
            0 => __DIR__ . '/..' . '/alecrabbit/php-cli-tools/src/Cli',
        ),
        'AlecRabbit\\' => 
        array (
            0 => __DIR__ . '/..' . '/alecrabbit/php-console-colour/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Pimple' => 
            array (
                0 => __DIR__ . '/..' . '/pimple/pimple/src',
            ),
            'ParsedownExtra' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown-extra',
            ),
            'Parsedown' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown',
            ),
        ),
        'N' => 
        array (
            'Net' => 
            array (
                0 => __DIR__ . '/..' . '/pear/net_smtp',
                1 => __DIR__ . '/..' . '/pear/net_socket',
            ),
        ),
        'M' => 
        array (
            'Mail' => 
            array (
                0 => __DIR__ . '/..' . '/pear/mail',
                1 => __DIR__ . '/..' . '/pear/mail_mime',
            ),
        ),
        'L' => 
        array (
            'Log' => 
            array (
                0 => __DIR__ . '/..' . '/pear/log',
            ),
        ),
        'D' => 
        array (
            'DB' => 
            array (
                0 => __DIR__ . '/..' . '/pear/db',
            ),
        ),
        'C' => 
        array (
            'Console' => 
            array (
                0 => __DIR__ . '/..' . '/pear/console_getopt',
            ),
        ),
        'A' => 
        array (
            'Auth' => 
            array (
                0 => __DIR__ . '/..' . '/pear/auth_sasl',
            ),
        ),
    );

    public static $fallbackDirsPsr0 = array (
        0 => __DIR__ . '/..' . '/pear/pear-core-minimal/src',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PEAR_Exception' => __DIR__ . '/..' . '/pear/pear_exception/PEAR/Exception.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1::$prefixesPsr0;
            $loader->fallbackDirsPsr0 = ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1::$fallbackDirsPsr0;
            $loader->classMap = ComposerStaticInita35dfb21368bb1c53a0460b7f8e424a1::$classMap;

        }, null, ClassLoader::class);
    }
}