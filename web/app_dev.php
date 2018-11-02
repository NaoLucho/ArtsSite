<?php
//ini_set('display_errors',1);
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read https://symfony.com/doc/current/setup.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.

if (isset($_SERVER['HTTP_CLIENT_IP'])
    //|| isset($_SERVER['HTTP_X_FORWARDED_FOR']) //block in prod, need to be comment to use console
    || !(in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', '2.4.15.190']) || PHP_SAPI === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit(' You are not allowed to access this file. Check '.basename(__FILE__).' for more information.'. $_SERVER['REMOTE_ADDR'].' A:'.isset($_SERVER['HTTP_CLIENT_IP']).' B:'.isset($_SERVER['HTTP_X_FORWARDED_FOR']).' C:'.!(in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', '2.4.15.190'])));
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';
Debug::enable();

$kernel = new AppKernel('dev', true);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
