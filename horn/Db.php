<?php
namespace Horn;

class Db{
    private $handler = null;
    private $config = array();
    private $logger = null;
    private static $instance = null;
    private $stm = null;   // 预编译对象
    private $stmsql = '';  // 最后预编译的 sql 语句
    
    public function __construct($logger, $dsn, $user, $pass, $extra=null){
        $this->logger = $logger;
        
        if(!$extra) {
            $extra = array(
                  \PDO::ATTR_PERSISTENT => false                     // 永久连接 2013/1/14 16:41:20
                , \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                , \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                , \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'    // 默认使用 utf8 内部编码
                , \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC  // 指定默认使用字段名索引 [这样就无法使用序号作为索引获取数据]
                , \PDO::ATTR_EMULATE_PREPARES => false
            );
        }
                
        $this->config = array(
            'dsn' => $dsn,
            'user' => $user,
            'pass' => $pass,
            'extra' => $extra
        );

        $this->Open();
    }
    
    public function Open(){
        if($this->handler != null) return; // 这里判断一下数据连接是否已经打开,如果已经打开过了则不浪费时间

        $config = $this->config;

        try {
            $this->handler = new \PDO($config['dsn'], $config['user'], $config['pass'], $config['extra']);
        }catch(\PDOException $e) {
            $this->logger->error($e->getMessage());
            throw new DbException($e->getMessage(), 1);
        }
    }
    
    public function Close(){
        if($this->handler != null){
            $this->handler = null;
        }
    }
    /* 预编译sql语句 并且释放上一次预编译对象
       2015-12-01 号碰到oo登录管理员的时候无法打开所有文件柜的抽屉列表，服务器返回报错：'Can\'t create more than max_prepared_stmt_count statements (current value: 16382)
       网上有说 max_prepared_stmt_count 参数限制了同一时间在mysqld上所有session中prepared 语句的上限。
        它的取值范围为“0 - 1048576”，默认为16382。
        mysql对于超出max_prepared_stmt_count的prepare语句就会报Can't create more than max_prepared_stmt_count statements (current value: 16382)"错误。
       
       后试验发现针对同一个 $this->handler ，如果有执行 query 或者 prepare 那么必须将上一个返回的对象关闭 closeCursor ，另外一个对象才能执行 execute 否则无法执行
    */
    public function Prepare(&$sql)
    { 
        // 这里判断一下本次的sql语句和上一次是否同一句，如果不是则关闭上一次的预编译
        $this->stm = null;
        if($this->stm == null || $this->stmsql != $sql) {
            $this->ClosePrepare();
            $this->stm = $this->handler->prepare($sql);
            $this->stmsql = $sql;
        } 
    }
    /* ExecPare 是和 Prepare 配套使用的
    */
    public function ExecPare(&$arr)
    { 
        if($this->stm == null) die('no stm');
        $resutl = $this->stm->fetchAll();  $resutl = null;                  
        return $this->stm->execute($arr);
    }
    public function ExecPare2(&$arr)
    { 
        if($this->stm == null) die('no stm');                
        return $this->stm->execute($arr);
    }
    public function ClosePrepare()
    { 
        if($this->stm != null) {/* $this->stm->fetchAll();*/ 
            $this->stm->closeCursor();
            $this->stm = null;       
        }  //    $this->stm->fetchAll() 去掉 ，因为如果之前执行的 update 的话那么这里获取数据会导致  General error: 2053 错误
    }
    public function __destruct(){
        $this->Close();
    }
    /*** 以上三个函数陪到使用 ***/
    protected function FormatError($e){
        //return date('Y-m-d H:i:s')."[_00_][".$_SERVER['REQUEST_URI']."]\r\n".$e->__toString()."\r\n";
        
        $log = date('Y-m-d H:i:s').'<pre>'. var_export($e,true).'</pre>\r\n\r\n';
        return $log;
        //return '['.date('Y-m-d H:i:s').']['.$e->getCode().']['.$e->getMessage().']'.$e->getTraceAsString();
    }
    
    /**
     * 返回数据库操作类句柄
     * Enter description here ...
     */
    public function GetHandler(){
        $this->Open();
        return $this->handler;
    }
    
    /**
     * 查询所有行
     * Enter description here ...
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function GetRows($sql, $arr=false, $CacheOptions = false, $juge=true){
        $this->Open();
        $rows = null;
        try{
            if($CacheOptions!==false && function_exists('S')){
                if($arr) $fname = md5($sql.var_export($arr,true)); else $fname = md5($sql);
                $rows = S('ListPage/'.$fname);  
            }
            if(empty($rows)) {                          
                if(is_array($arr) && count($arr)>0){
                    $this->Prepare($sql);
                    $this->ExecPare($arr);
                    $rows = $this->stm->fetchAll();
                    /*
                    $stm = $this->handler->prepare($sql);
                    $stm->execute($arr);
                    $rows = $stm->fetchAll();
                    */
                }else{
                    $this->ClosePrepare();
                    $stm = $this->handler->query($sql);
                    $rows = $stm->fetchAll();
                    $stm->closeCursor();
                }
            }
            if($CacheOptions!==false&& function_exists('S')) S('ListPage/'.$fname,$rows,$CacheOptions);   // 缓存数据以及设定 time 超时时间
             
        }catch (\PDOException $e){
            $this->Close();
            // error_log($this->FormatError($e), 3, LOGPATH.$this->log);
            // die(E::$db_findall);
            $this->logger->error($e->getMessage());
            throw new DbException($e->getMessage(), 1);
        }
        return self::IsRows($rows)?$rows:null;
    }
    
    /**
     * 查询一行
          以数组形式返回一行数据,字段名称为数组下标
     * Enter description here ...
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function GetRow($sql, $arr=false,$CacheOptions = false,$juge=true){
        // 最好检查一下 $sql 是否有 limit , 如果没有,则在末尾增加 limit 1,确保只返回一行,避免浪费效率 [不过调用者也应该自行考虑这个]
        
        $row = $this->GetRows($sql,$arr,$CacheOptions,$juge);
        if(self::IsRows($row))
            $row= $row[0];
        else
            $row=null;
        return $row; // 返回第一行
    }
    // 返回第一列组成的数组
    public function GetArr($sql, $arr=array())
    {
        $r = $this->GetRows($sql, $arr);
        $rtv = array();
        foreach($r as  &$row){
            foreach($row as &$v) {
                $rtv[] = $v;
                break;
            };
        }
        return $rtv;
    }
    /**
     * 执行sql insert/update/delete等
     * Enter description here ...
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function Exec($sql, $arr=false){
        $this->Open();
        $result = null;
        try{
            if(is_array($arr)){
                $this->Prepare($sql);
                $this->ExecPare($arr);
                $result = $this->stm->rowCount();
                /*
                $stm = $this->handler->prepare($sql);
                $stm->execute($arr);
                $result = $stm->rowCount();
                */
            }else{
                $this->ClosePrepare();
                $result = $this->handler->exec($sql);
            }
        }catch (\PDOException $e){
            $this->Close();
            // error_log($this->FormatError($e), 3, LOGPATH.$this->log);
            // die(E::$db_exec.$sql);
            $this->logger->error($e->getMessage());
            throw new DbException($e->getMessage(), 1);
        }
        return $result;
    }
    
    /**
     * 执行insert 返回自增id
     * Enter description here ...
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function Insert($sql, $arr=array()){
        $this->Open();
        $ret = false;
        try{
            /*
            $stm = $this->handler->prepare($sql);
            $stm->execute($arr);
            $count = $stm->rowCount();
            */
            $this->Prepare($sql);
            $this->ExecPare($arr);
            $count = $this->stm->rowCount();
            if($count===false)$ret = false;
            if($count>0){
                $ret = $this->handler->lastInsertId(); 
            }
            return $ret;
        }catch (\PDOException $e){
            $this->Close();
            $this->logger->error($e->getMessage());
            throw new DbException($e->getMessage(), 1);
            
            // error_log($this->FormatError($e), 3, LOGPATH.$this->log);
            // die(E::$db_insert);
        }
    }
    public function InsertRow($sql, $arr=array()){
        return self::Insert($sql,$arr);
    }
    /**
     * 查询某字段
     * Enter description here ...
     * @param $name
     * @param $sql
     * @param $arr
     */
    public function GetField($name, $sql, $arr=array()){
        $row = $this->GetRow($sql,$arr);
        if(self::IsRow($row)){
            if($name==='' || $name===false){ // 如果索引为 空,那么表示返回第一列
                foreach($row as &$v) return $v;
            }
            return isset($row[$name]) ? $row[$name]:false;
        }
        return false;
    }
    
    /**
     * 查询某字段 数字类型
     * Enter description here ...
     * @param unknown_type $name
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function GetNum($sql, $arr=array(),$defaultV=0,$name = false){
        $v = $this->GetField($name, $sql, $arr);
        return ($v===false)? $defaultV:$v;
    }
    
    public function GetSplit($sql, $arr=array(),$defaultV='',$name = false){
        $rows = $this->GetRows($sql,$arr);
        if(self::IsRows($rows)){
            $ids = '';
            foreach($rows as &$row){
                foreach($row as &$_id){
                    if($ids != '') $ids  .= ',';
                    $ids .= $_id;
                    break;
                }
            }
            return $ids;
        }else{
            return $defaultV;
        }
    }
    
    /**
     * 查询某字段 字符串
     * Enter description here ...
     * @param unknown_type $name
     * @param unknown_type $sql
     * @param unknown_type $arr
     */
    public function GetStr($sql, $arr=array(),$defaultV='',$name=false){
        $v = $this->GetField($name, $sql, $arr);
        return ($v===false)? $defaultV:$v;
    }
    
    /**
     * 检查查询结果 是否是正确的一行
     */
    public static function IsRow($row){
        if(is_array($row)){
            return true;
        }
        return false;
    }
    
    /**
     * 检查查询结果 是否是正确的行集
     * Enter description here ...
     * @param unknown_type $rows
     */
    public static function IsRows($rows){
        if(is_array($rows) && isset($rows[0])){
            return true;
        }
        return false;
    }
    
}