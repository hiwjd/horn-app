<?php
namespace Horn;

use Psr\Log\LoggerInterface;
use Horn\IdGen;

class Group
{

    private $logger;
    private $db;

    public function __construct(LoggerInterface $logger, Db $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function get($oid)
    {
        $sql = "select * from groups where oid = ?";
        $rows = $this->db->GetRows($sql, array($oid));
        if(!is_array($rows)) {
            $rows = array();
        }
        return $rows;
    }

    public function edit($oid, $gid, $name)
    {
        $sql = "update groups set name = ? where oid = ? and gid = ?";
        if($this->db->Exec($sql, array($name, $oid, $gid)) >= 0) {
            return true;
        }

        throw new Exception("保存失败", 500);
    }

    public function add($oid, $name)
    {
        $gid = IdGen::gid();
        $sql = "insert into groups(gid,oid,name) values(?,?,?)";
        if($this->db->Exec($sql, array($gid, $oid, $name)) >= 0) {
            return $gid;
        }

        throw new Exception("保存失败", 500);
    }

}