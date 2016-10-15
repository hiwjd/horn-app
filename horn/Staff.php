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

    public function create($clientId, $data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $pass = isset($data['pass']) ? $data['pass'] : '';
        $tel = isset($data['tel']) ? $data['tel'] : '';
        $pass = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "insert into staff(client_id,name,mobile,email,pass,tel)values(?,?,?,?,?,?)";
        return $this->db->Insert($sql, array($clientId, $name, $mobile, $email, $pass, $tel));
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

    public function sendSignupEmail($email) {
        $s = Util::randStr(24);
        $expires_in = 60*60;
        $this->db->Insert($sql, array($s, $email, $expires_in));

        echo "http://www.horn.com:9092/signup/verify_email?s=$s";
    }

    public function checkEmailByS($s) {
        $sql = "select * from signup_email where s = ?";
        $row = $this->db->GetRow($sql, array($s));
        if(!$row) {
            return array(1009, "无效链接");
        }

        if(time() > (strtotime($row["created_at"]) + $row["expires_in"])) {
            return array(1003, "链接超时了，请重新发送确认邮件");
        }

        $email = $row["email"];
        $sql = "update staff set status = ?, updated_at = ?, pass = ? where email = ?";
        $this->db->Exec($sql, array(self::ACTIVE, date("Y-m-d H:i:s"), '', $email));
        $this->db->Exec("delete from signup_email where s = ?", array($s));

        return array(0, $email);
    }

    public function genActiveToken($email) {
        $sql = "insert signup_email(email,token,expires_at)values(?,?,?) on duplicate key update token=?, expires_at=?";
        $token = Util::randStr(50);
        $expires_at = time() + 7200;
        $this->db->Exec($sql, array($email, $token, $expires_at, $token, $expires_at));

        return $token;
    }
}