<?php
namespace Controller;

use Slim\Container as ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Horn\Util;

class TagController
{

    protected $ci;

    public function __construct(ContainerInterface $ci)
    {
        $this->ci = $ci;
    }

    public function get(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");

        $tags = $this->ci->tag->get($oid);

        return $rsp->withJson($tags);
    }

    public function getByVisitor(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $vid = $req->getParam("vid");

        $tags = $this->ci->tag->getByVisitor($oid, $vid);

        return $rsp->withJson($tags);
    }

    public function add(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $name = $req->getParam("name");
        $color = $req->getParam("color");

        $sid = $req->getAttribute("sid");

        $this->ci->tag->add($oid, $name, $color, $sid);

        return $rsp->withJson(Util::BeJson('添加成功', 200));
    }

    public function edit(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $name = $req->getParam("name");
        $color = $req->getParam("color");
        $tagId = $req->getParam("tag_id");

        $this->ci->tag->edit($oid, $tagId, $name, $color);

        return $rsp->withJson(Util::BeJson('编辑成功', 200));
    }

    public function save(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $name = $req->getParam("name");
        $color = $req->getParam("color");
        $tagId = $req->getParam("tag_id");
        $sid = $req->getAttribute("sid");

        if($tagId > 0) {
            $this->ci->tag->edit($oid, $tagId, $name, $color);
        } else {
            $this->ci->tag->add($oid, $name, $color, $sid);
        }

        return $rsp->withJson(Util::BeJson('保存成功', 200));
    }

    public function delete(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $tagId = $req->getParam("tag_id");

        $this->ci->tag->delete($oid, $tagId);

        return $rsp->withJson(Util::BeJson('删除成功', 200));
    }

    public function attach(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $tagId = $req->getParam("tag_id");
        $vid = $req->getParam("vid");

        $sid = $req->getAttribute("sid");

        $this->ci->tag->attach($oid, $vid, $tagId, $sid);

        return $rsp->withJson(Util::BeJson('添加成功', 200));
    }

    public function detach(Request $req, Response $rsp, $args)
    {
        $oid = $req->getParam("oid");
        $tagId = $req->getParam("tag_id");
        $vid = $req->getParam("vid");

        $this->ci->tag->detach($oid, $vid, $tagId);

        return $rsp->withJson(Util::BeJson('删除成功', 200));
    }

}