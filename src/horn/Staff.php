<?php
namespace Horn;

use Hashids\Hashids;

class Staff {

    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const PENDING = 'pending';

    private $db;

    public function __construct(Db $db) {
        $this->db = $db;
    }

    public function create($cid, $data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $pass = isset($data['pass']) ? $data['pass'] : '';
        $tel = isset($data['tel']) ? $data['tel'] : '';
        $pass = password_hash($pass, PASSWORD_DEFAULT);
        $staffId = IdGen::staffId();
        $sql = "insert into staff(staff_id,cid,name,mobile,email,pass,tel)values(?,?,?,?,?,?,?)";
        $this->db->Exec($sql, array($staffId, $cid, $name, $mobile, $email, $pass, $tel));

        return $staffId;
    }

    public function findByEmail($email) {
        $sql = "select * from staff where email = ?";
        return $this->db->GetRow($sql, array($email));
    }

    public function auth($data) {
        $email = isset($data['email']) ? $data['email'] : '';
        $pass = isset($data['pass']) ? $data['pass'] : '';

        $sql = "select * from staff where email=?";
        $user = $this->db->GetRow($sql, array($email));
        if(!$user) {
            throw new Exception("邮箱或密码错误", 1002);
        }

        if($user['status'] != Staff::ACTIVE) {
            if($user['status'] == Staff::PENDING) {
                throw new Exception("请先完成注册", 1002);
            }else if($user['status'] == Staff::INACTIVE) {
                throw new Exception("此帐号暂不可用", 1002);
            }else {
                throw new Exception("未知错误", 1002);
            }
        }

        if(!password_verify($pass, $user["pass"])) {
            throw new Exception("邮箱或密码错误", 1002);
        }

        unset($user['pass']);
        return $user;
    }

    public function confirmEmail($token) {
        $sql = "select * from signup_email where token = ? and state = 'valid'";
        $row = $this->db->GetRow($sql, array($token));
        if(!$row) {
            throw new WrongArgException("无效链接");
        }

        if(time() > $row["expires_at"]) {
            throw new ExpiresSignupEmailException("链接超时了，请重新发送确认邮件");
        }

        $email = $row["email"];
        $sql = "update staff set status = ?, updated_at = ? where email = ?";
        $this->db->Exec($sql, array(self::ACTIVE, date("Y-m-d H:i:s"), $email));
        $this->db->Exec("update signup_email set state = 'invalid' where token = ?", array($token));

        return $email;
    }

    public function getFindPassByToken($token) {
        $sql = "select * from find_pass_email where token = ? and state = 'valid'";
        $row = $this->db->GetRow($sql, array($token));
        if(!$row) {
            throw new WrongArgException("无效链接");
        }

        if(time() > $row["expires_at"]) {
            throw new ExpiresSignupEmailException("链接超时了");
        }

        return $row;
    }

    public function resetPass($token, $newPass) {
        $row = $this->getFindPassByToken($token);

        $email = $row["email"];
        $sql = "update staff set pass = ?, updated_at = ? where email = ?";
        $pass = password_hash($newPass, PASSWORD_DEFAULT);
        $this->db->Exec($sql, array($pass, date("Y-m-d H:i:s"), $email));
        $this->db->Exec("update find_pass_email set state = 'invalid' where token = ?", array($token));

        return $email;
    }
}