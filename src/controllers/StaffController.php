<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;

class StaffController {
    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function info(Request $req, Response $rsp, $args) {
        if(isset($_SESSION['staff']) && is_array($_SESSION['staff'])) {
            $staff = $_SESSION["staff"];
            $staffId = $staff["sid"];
            $oid = $staff["oid"];
            $tid = date("YmdHis").$staffId.Util::randStr(16);
            $org = $this->ci->org->findById($oid);

            return $rsp->withJson(array(
                "code" => 0,
                "msg" => "",
                "oid" => $oid,
                "sid" => $staffId,
                "tid" => $tid,
                "staff" => $staff,
                "org" => $org
            ));
        }

        return $rsp->withJson(array(
            "code" => 1009,
            "msg" => ""
        ));
    }

    public function online(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $staffs = $this->ci->store->getOnlineStaff($oid);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "",
            "data" => $staffs
        ));
    }

}