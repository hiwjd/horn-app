<?php
namespace Horn;

class Client {

    private $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function create($data) {
        $name = $data['name'];
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $sql = "insert into client(name,mobile,email)values(?,?,?)";
        $id = $this->db->Insert($sql, array($name, $mobile, $email));

        if(!$id) {
            throw new Exception("创建失败", 10002);
        }

        $code = Util::genClienCode($id);
        $sql = "update client set code = ? where id = ?";
        $this->db->Exec($sql, array($code, $id));

        return $id;
    }

    public function findByName($name) {
        $sql = "select * from client where name = ?";
        return $this->db->GetRow($sql, array($name));
    }
}