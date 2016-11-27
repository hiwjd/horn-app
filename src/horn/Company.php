<?php
namespace Horn;

class Company {

    private $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function findByName($name) {
        $sql = "select * from company where name = ?";
        return $this->db->GetRow($sql, array($name));
    }

    public function create($name, $email="") {
        $code = IdGen::cid();
        $sql = "insert into company(code,name,email) values(?,?,?)";
        return $this->db->Insert($sql, array($code, $name, $email));
    }

    public function findById($cid) {
        $sql = "select * from company where cid = ?";
        return $this->db->GetRow($sql, array($cid));
    }
}