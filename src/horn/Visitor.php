<?php
namespace Horn;

use Psr\Log\LoggerInterface;

class Visitor
{

    private $logger;
    private $db;

    public function __construct(LoggerInterface $logger, Db $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function edit($oid, $vid, $data)
    {
        //var_dump($data);die();
        $fields = ["name","gender","age","mobile","email","qq","addr","note"];

        $arr = [];
        $vArr = [];
        foreach($data as $k => $v) {
            if(in_array($k, $fields)) {
                $arr[] = "$k=?";
                $vArr[] = $v;
            }
        }
        $sqlup = implode($arr, ",");
        $vArr[] = $oid;
        $vArr[] = $vid;

        $sql = "update visitors set $sqlup where oid=? and vid=?";
        if($this->db->Exec($sql, $vArr) >= 0) {
            return true;
        }

        throw new Exception("保存失败", 500);
    }

}