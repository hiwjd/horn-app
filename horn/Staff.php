<?php
namespace Horn;

class Staff {

    private $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function create($clientId, $data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $passwd = isset($data['passwd']) ? $data['passwd'] : '';
        $tel = isset($data['tel']) ? $data['tel'] : '';
        $passwd = password_hash($passwd, PASSWORD_DEFAULT);
        $sql = "insert into staff(client_id,name,mobile,email,passwd,tel)values(?,?,?,?,?,?)";
        return $this->db->Insert($sql, array($clientId, $name, $mobile, $email, $passwd, $tel));
    }

    public function findByEmail($email) {
        $sql = "select * from staff where email = ?";
        return $this->db->GetRow($sql, array($email));
    }

    public function auth($data) {
        $email = isset($data['email']) ? $data['email'] : '';
        $pwd = isset($data['pwd']) ? $data['pwd'] : '';

        $sql = "select * from staff where email=?";
        $user = $this->db->GetRow($sql, array($email));
        if(!$user) {
            return false;
        }

        if(!password_verify($pwd, $user['passwd'])) {
            return false;
        }

        unset($user['passwd']);
        return $user;
    }
}