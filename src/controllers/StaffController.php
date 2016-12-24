<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;
use Horn\Queue;
use Horn\IdGen;
use Horn\MissingArgException;

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

    public function get(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $page = $req->getParam("page");
        $size = $req->getParam("size");

        $cond = array(
            "oid" => $oid,
            "page" => $page,
            "size" => $size
        );

        $r = $this->ci->staff->getList($cond);

        return $rsp->withJson(array(
            "code" => 0,
            "msg" => "ok",
            "data" => $r["data"],
            "total" => $r["total"]
        ));
    }

    public function save(Request $req, Response $rsp, $args) {
        // $oid = $req->getParam("oid");
        $sid = $req->getParam("sid");
        // $name = $req->getParam("size");
        // $gender = $req->getParam("oid");
        // $mobile = $req->getParam("page");
        // $email = $req->getParam("size");
        // $qq = $req->getParam("oid");
        $data = $req->getParams();
        
        if($sid != "") {
            $this->ci->staff->edit($data);
        } else {
            $sid = IdGen::sid();
            $data["sid"] = $sid;
            $this->ci->staff->add($data);
        }

        return $rsp->withJson(array(
            "code" => 200,
            "msg" => "保存成功",
            "sid" => $sid
        ));
    }

    public function editpwd(Request $req, Response $rsp, $args) {
        $oid = $req->getParam("oid");
        $sid = $req->getParam("sid");
        $pwd = $req->getParam("pwd");

        if(!$oid || !$sid || !$pwd) {
            throw new MissingArgException("参数错误");
        }

        $this->ci->staff->editpwd($oid, $sid, $pwd);

        return $rsp->withJson(array(
            "code" => 200,
            "msg" => "保存成功"
        ));
    }

}