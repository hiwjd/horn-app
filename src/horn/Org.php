<?php
namespace Horn;

class Org {

    private $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function findByName($name) {
        $sql = "select * from orgs where name = ?";
        return $this->db->GetRow($sql, array($name));
    }

    public function create($name, $email="") {
        $code = IdGen::oid();
        $sql = "insert into orgs(code,name,email) values(?,?,?)";
        return $this->db->Insert($sql, array($code, $name, $email));
    }

    public function findById($oid) {
        $sql = "select * from orgs where oid = ?";
        return $this->db->GetRow($sql, array($oid));
    }
}