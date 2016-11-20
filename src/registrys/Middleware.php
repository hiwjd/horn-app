<?php
namespace Registry;

use Slim\App;
use Middleware\CorsMiddleware;
use Middleware\LoggerMiddleware;
use RKA\Middleware\IpAddress;

class Middleware {

    public static function register(App $app) {
        $container = $app->getContainer();

        // 跨域头
        $app->add(new CorsMiddleware($container));

        // 日志中间件
        $app->add(new LoggerMiddleware($container));

        $checkProxyHeaders = true;
        //$trustedProxies = ['10.0.0.1', '10.0.0.2'];
        $app->add(new IpAddress($checkProxyHeaders));
    }

}