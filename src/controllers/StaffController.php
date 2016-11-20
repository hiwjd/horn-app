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
            $staffId = $staff["staff_id"];
            $cid = $staff["cid"];
            $trackId = date("YmdHis").$staffId.Util::randStr(16);

            return $rsp->withJson(array(
                "code" => 0,
                "msg" => "",
                "cid" => $cid,
                "staff_id" => $staffId,
                "track_id" => $trackId,
                "staff" => $staff
            ));
        }

        return $rsp->withJson(array(
            "code" => 1009,
            "msg" => ""
        ));
    }

    public function online(Request $req, Response $rsp, $args) {
        $cid = $req->getParam("cid");
        $staffs = $this->ci->store->getOnlineStaff($cid);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "",
            "data" => $staffs
        ));
    }

}