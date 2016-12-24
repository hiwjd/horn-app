<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;

class VisitorController {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function edit(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $vid = $req->getParam("vid");
        $data = $req->getParams();
        unset($data["oid"]);
        unset($data["vid"]);

        $this->ci->visitor->edit($oid, $vid, $data);

        return $rsp->withJson(Util::BeJson('修改成功', 200));
    }

}