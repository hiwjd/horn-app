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

    public function create($oid, $data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $pass = isset($data['pass']) ? $data['pass'] : '';
        $tel = isset($data['tel']) ? $data['tel'] : '';
        $pass = password_hash($pass, PASSWORD_DEFAULT);
        $staffId = IdGen::sid();
        $sql = "insert into staff(sid,oid,name,mobile,email,pass,tel)values(?,?,?,?,?,?,?)";
        $this->db->Exec($sql, array($staffId, $oid, $name, $mobile, $email, $pass, $tel));

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
        $sql = "select * from email_tokens where intention = 'signup' and token = ? and state = 'valid'";
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
        $this->db->Exec("update email_tokens set state = 'invalid' where token = ?", array($token));

        return $email;
    }

    public function getFindPassByToken($token) {
        $sql = "select * from email_tokens where intention = 'resetpass' and token = ? and state = 'valid'";
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
        $this->db->Exec("update email_tokens set state = 'invalid' where token = ?", array($token));

        return $email;
    }

    public function getList($cond) {
        $oid = $cond["oid"];
        $page = $cond["page"];
        $size = $cond["size"];
        $offset = ($page-1)*$size;
        $sql = "select sid,oid,name,gender,mobile,email,tel,qq,status,state from staff where oid = ? order by created_at desc limit $offset,$size";
        $rows = $this->db->GetRows($sql, array($oid));
        $total = $this->db->GetNum("select count(1) from staff where oid = ?", array($oid));

        return array(
            "data" => $rows,
            "total" => $total
        );
    }

    public function add($data) {
        //$this->logger->info("Tag.add oid[$oid] name[$name] color[$color] sid[$sid]");
        $sql = "insert into staff(sid,oid,name,gender,mobile,email,tel,qq,status) values (?,?,?,?,?,?,?,?,?)";
        if($this->db->Exec($sql, array(
            $data["sid"],
            $data["oid"],
            $data["name"],
            $data["gender"],
            $data["mobile"],
            $data["email"],
            $data["tel"],
            $data["qq"],
            $data["status"]
        )) == 1) {
            //$this->logger->info(" -> 成功");
            return true;
        }

        //$this->logger->info(" -> 失败");
        throw new Exception("添加失败", 500);
    }

    public function edit($data) {
        $oid = $data["oid"];
        $sid = $data["sid"];
        unset($data["oid"]);
        unset($data["sid"]);

        $fields = ["name","gender","mobile","email","tel","qq","status"];
        $arr = array();
        $vArr = array();
        foreach($data as $k => $v) {
            if(in_array($k, $fields)) {
                $arr[] = "$k=?";
                $vArr[] = $v;
            }
        }
        $upsql = implode(",", $arr);
        $vArr[] = $oid;
        $vArr[] = $sid;
        //$this->logger->info("Tag.edit oid[$oid] tagId[$tagId] color[$color]");
        $sql = "update staff set $upsql where oid = ? and sid = ?";
        if($this->db->Exec($sql, $vArr) >= 0) {
            //$this->logger->info(" -> 成功");
            return true;
        }

        //$this->logger->info(" -> 失败");
        throw new Exception("编辑失败", 500);
    }

    public function editpwd($oid, $sid, $pwd) {
        $pass = password_hash($pwd, PASSWORD_DEFAULT);
        $sql = "update staff set pass = ? where oid = ? and sid = ?";
        if($this->db->Exec($sql, array($pass, $oid, $sid)) >= 0) {
            return true;
        }

        throw new Exception("修改密码失败", 500);
    }
}