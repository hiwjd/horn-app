<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class IdentityController {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function identity(Request $req, Response $rsp, $args) {
        $fp = $req->getParam("fp");
        if(!$fp) {
            return $rsp->withJson(array(
                "code" => 1,
                "msg" => "缺少必要参数"
            ));
        }

        $uid = $this->ci->store->getUidByFP($fp);
        if(!$uid) {
            $uid = Util::randStr(23);
            $this->ci->store->setUidByFP($fp, $uid);
        }
        
        return $rsp->withJson(array(
            "code" => 0,
            "msg" => ""
        ));
    }

}