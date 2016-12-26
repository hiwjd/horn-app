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
        $accessKey = "ck4n4y-ddTGGDGhBgGL9sAogPO8Kr2Ya80GRtgGN";
        $secretKey = "8e-UIP3UyYZkjaKyYg9U-XxDCj6VS2owtQNlOPbO";
        $bucket = "f1stxtgl";

        $auth = new Auth($accessKey, $secretKey);

        $token = $auth->uploadToken($bucket);

        return $rsp->withJson(array(
            "uptoken" => $token
        ));
    }

}