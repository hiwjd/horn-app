<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\CaptachType;
use Qiniu\Auth;

class QiniuController
{
    protected $ci;

    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
    }

    public function uptoken(Request $req, Response $rsp, $args)
    {
        $qiniuCfg = $this->ci->config["qiniu"];
        $accessKey = $qiniuCfg["accessKey"];
        $secretKey = $qiniuCfg["secretKey"];
        $bucket = $qiniuCfg["bucket"];

        $auth = new Auth($accessKey, $secretKey);

        $token = $auth->uploadToken($bucket);

        return $rsp->withJson(array(
            "uptoken" => $token
        ));
    }

    public function uptoken_options(Request $req, Response $rsp, $args)
    {
        return $rsp->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader("Access-Control-Allow-Methods", "GET,OPTIONS")
                ->withHeader("Access-Control-Allow-Headers", "content-type")
                ->withHeader("Access-Control-Allow-Headers", "If-Modified-Since");
    }

}