<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;

class GroupController {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function get(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");

        $groups = $this->ci->group->get($oid);

        return $rsp->withJson($groups);
    }

    public function save(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $gid = $req->getParam("gid");
        $name = $req->getParam("name");

        if($gid == "") {
            $gid = $this->ci->group->add($oid, $name);
        } else {
            $this->ci->group->edit($oid, $gid, $name);
        }

        return $rsp->withJson(Util::BeJson('保存成功', 200));
    }

}