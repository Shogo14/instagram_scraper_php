<?php

require __DIR__ . '/config.php';    //ライブラリロード

class DB_Adapter{
    protected $_connection = null;

    public function __construct()
    {
        $this->db_connect();
    }

    public function __destruct()
    {
        $this->close();
    }
    public function db_connect(){
        // 既に接続している場合はリターン
        if($this->isConnected()) {
            return;
        }
        try {
            $this->_connection = new PDO(DSN, DB_USERNAME, DB_PASSWORD);
            $this->_connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
          } catch (\PDOException $e) {
            echo mb_convert_encoding($e->getMessage(), "UTF-8", "Shift-JIS");
            exit;
          }
    }

    public function getAccountData(){
          
        $sql = "select d.profinfo13,d.name2
        from data as d
        left join not_get_account_list ngal
        on d.profinfo13 = ngal.profinfo13
        where d.profinfo13 != ''
        and ngal.profinfo13 is null
        order by d.profinfo13";
        $stmt = $this->_connection->query($sql);
        return $stmt->fetchAll();
    }
    

    public function AlreadyExistPost($post_id){

        $sql = sprintf("select COUNT(*) FROM posts WHERE post_id = '%s'",$post_id);
        $count = (int)$this->_connection->query($sql)->fetchColumn();
        if($count == 0){
            return false;
        }else{
            return true;
        }
    }
    
    public function getPostCountByUser(){
          
        $sql = "select param_value from system_param where param_name = 'PostCountByUser'";
        $stmt = $this->_connection->query($sql);
        return $stmt->fetch();
    }
    public function getOnceGetPostLimit(){
          
        $sql = "select param_value from system_param where param_name = 'OnceGetPostLimit'";
        $stmt = $this->_connection->query($sql);
        return $stmt->fetch();
    }
    public function getOnceGetWaitSecond(){
          
        $sql = "select param_value from system_param where param_name = 'OnceGetWaitSecond'";
        $stmt = $this->_connection->query($sql);
        return $stmt->fetch();
    }
    public function getLimitGetWaitSecond(){
          
        $sql = "select param_value from system_param where param_name = 'LimitGetWaitSecond'";
        $stmt = $this->_connection->query($sql);
        return $stmt->fetch();
    }

    
    public function getStartCountData(){

        $sql = sprintf("select seq from data_acquire_state where seq = (select max(seq) from data_acquire_state)");
        $max_count = (int)$this->_connection->query($sql)->fetchColumn();
        return $max_count;
    }
    
    public function InsertDataAcquireState($post_id){
        $sql = "insert INTO data_acquire_state(post_id) VALUES (:post_id)";
        $stmt = $this->_connection->prepare($sql);

        //トランザクション処理
        $this->beginTransaction();
    
        try{
            $stmt->bindParam(':post_id', $post_id);
            $stmt->execute();
            $this->commit();
        }catch (PDOException $e) {
            //ロールバック
            $this->rollback();
            throw $e; //
        }
    }



    public function TruncateDataAcquireState(){
        $sql = sprintf("truncate table data_acquire_state");
        $this->_connection->exec($sql);
    }

    

    public function InsertPosts($post_id, $data_name2, $caption, $user_id, $posted_date){
        $sql = "insert INTO posts(post_id,data_name2,caption,user_id,posted_date) 
                            VALUES (:post_id,:data_name2,:caption,:user_id,:posted_date)";
        $stmt = $this->_connection->prepare($sql);

        //トランザクション処理
        $this->beginTransaction();
    
        try{
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':data_name2', $data_name2);
            $stmt->bindParam(':caption', $caption);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':posted_date', $posted_date);

            $nameset = $this->_connection->prepare("SET NAMES utf8mb4");
            $charaset = $this->_connection->prepare("SET CHARACTER SET utf8mb4");
            $charaset_connection = $this->_connection->prepare("SET character_set_connection=utf8mb4");
            
            $nameset->execute();
            $charaset->execute();
            $charaset_connection->execute();
            
            $stmt->execute();
    
            //コミット
            $this->commit();
    
        }catch (PDOException $e) {
            //ロールバック
            $this->rollback();
            throw $e; //
        }
    }
    public function InsertPostMedia($post_id,$media_type,$src){
        $sql = "insert INTO post_media(post_id,media_type,src) 
                            VALUES (:post_id,:media_type,:src)";
        $stmt = $this->_connection->prepare($sql);

        //トランザクション処理
        $this->beginTransaction();
    
        try{
            $stmt->bindParam(':post_id', $post_id);
            $stmt->bindParam(':media_type', $media_type);
            $stmt->bindParam(':src', $src);
            
            $stmt->execute();
    
            //コミット
            $this->commit();
    
        }catch (PDOException $e) {
            //ロールバック
            $this->rollback();
            throw $e; //
        }

    }

    
    public function InsertNotGetAccountList($profinfo13,$name2,$reason){
        $sql = "insert INTO not_get_account_list(profinfo13,name2,reason) 
                                        VALUES (:profinfo13,:name2,:reason)";
        $stmt = $this->_connection->prepare($sql);

        //トランザクション処理
        $this->beginTransaction();
    
        try{
            $stmt->bindParam(':profinfo13', $profinfo13);
            $stmt->bindParam(':name2', $name2);
            $stmt->bindParam(':reason', $reason);
            
            $stmt->execute();
    
            //コミット
            $this->commit();
    
        }catch (PDOException $e) {
            //ロールバック
            $this->rollback();
            throw $e; //
        }

    }
      
    public function isConnected()
    {
        return ((bool) ($this->_connection instanceof PDO));
    }
    
    public function close() 
    {
        $this->_connection = null;
    }

    public function beginTransaction()
    {
        $this->_connection->beginTransaction();
        return $this;
    }
     
    public function commit()
    {
        $this->_connection->commit();
        return $this;
    }
 
    public function rollback()
    {
        $this->_connection->rollBack();
        return $this;
    }
}


