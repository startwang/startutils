<?php
/*======================================================================================================================
 *   ProjectName: startutils
 *      FileName: Pdo.php
 *          Desc: pdo数据库操作类
 *        Author: start
 *         Email: start_wang@qq.com
 *      HomePage: 
 *       Version: 0.0.1
 *           IDE: PhpStorm
 *  CreationTime: 2017/3/7 11:29
 *    LastChange: 2017/3/7 11:29
 *       History:
 =====================================================================================================================*/

namespace Start\Utils;

class Pdodb
{
    /**
     * 单例
     * @var null
     */
    protected static $_instance = null;

    /**
     * 数据库名称
     * @var string
     */
    protected $dbName = '';

    /**
     * 连接
     * @var
     */
    protected $link;

    /**
     * PdoDatabase constructor.
     * @param $host
     * @param $user
     * @param $pwd
     * @param $dbName
     * @param string $type
     * @param string $charset
     */
    public function __construct($host, $user, $pwd, $dbName, $type = 'mysql', $charset = 'utf8')
    {
        try{
            if($type == 'mysql'){
                $dsn = 'mysql:host=' . $host . ';dbname=' . $dbName . ';charset='.$charset;
            } else{
                $dsn = 'sqlsrv:Server=' . $host.  ';Database=' . $dbName . ';charset=' . $charset;
            }
            $this->link = new \PDO($dsn, $user, $pwd);
        } catch (\PDOException $e){
            $this->showError($e->getMessage());
        }
    }

    /**
     * 防止克隆
     * @author start
     */
    private function __clone()
    {
    }

    /**
     * 单例
     * @param $host
     * @param $user
     * @param $pwd
     * @param $dbName
     * @param string $type
     * @param string $charset
     * @return null|PdoDatabase
     * @author start
     */
    public static function getInstance($host, $user, $pwd, $dbName, $type = 'mysql', $charset = 'utf8')
    {
        if(self::$_instance === null){
            self::$_instance = new self($host, $user, $pwd, $dbName, $type, $charset);
        }
        return self::$_instance;
    }

    /**
     * 查询
     * @param $sql				sql语句
     * @param string $queryMode   查询方式 All or Row
     * @return array|mixed|null
     * @author start
     */
    public function query($sql, $queryMode = 'All')
    {
        $recordSet = $this->link->query($sql);
        $this->getPDOError();
        if($recordSet){
            $recordSet->setFetchMode(\PDO::FETCH_ASSOC);
            if($queryMode == 'All'){
                $result = $recordSet->fetchAll();
            } else {
                $result = $recordSet->fetch();
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * 插入数据
     * @param $table	表名
     * @param $data	数组数据
     * @return int
     * @author start
     */
    public function insert($table, $data)
    {
        $this->checkFields($table, $data);
        $sql = 'INSERT INTO ' . $table . ' (' .implode(',', array_keys($data)) . ') VALUES ("' . implode('","', $data) . '")';
        echo  $sql;
        $result = $this->link->exec($sql);
        $this->getPDOError();
        return $result;
    }

    /**
     * 更新数据
     * @param $table		表名
     * @param $data		数组数据
     * @param $where		条件
     * @return bool|int
     * @author start
     */
    public function update($table, $data, $where)
    {
        if(empty($where)) return false;
        $this->checkFields($table, $data);
        $sql = '';
        foreach($data as $key => $value){
            $sql .= ', ' . $key . '="' . $value.'"';
        }
        $sql = substr($sql, 1);
        $sql = 'UPDATE ' . $table . ' SET ' . $sql . ' WHERE ' . $where;
        $result = $this->link->exec($sql);
        $this->getPDOError();
        return $result;
    }

    /**
     * 删除数据
     * @param $table		表名
     * @param $where		条件
     * @return bool|int
     * @author start
     */
    public function delete($table, $where)
    {
        if(empty($where)) return false;
        $sql = 'DELETE FROM ' . $table . ' WHERE ' . $where;
        $result = $this->link->exec($sql);
        $this->getPDOError();
        return $result;
    }

    /**
     * 查询列数量
     * @param $table
     * @param $fieldName
     * @param string $where
     * @return mixed
     * @author start
     */
    public function getCount($table, $fieldName, $where = '')
    {
        $sql = 'SELECT COUNT(' . $fieldName . ') AS NUM FROM ' . $table;
        if ($where != '') $sql .= " WHERE $where";
        $arrTemp = $this->query($sql, 'Row');
        return $arrTemp['NUM'];
    }

    /**
     * 获取最后插入id
     * @return bool|string
     * @author start
     */
    public function lastInsertId(){
        if(!$this->link){
            return false;
        }
        return $this->link->lastInsertId();
    }

    /**
     * 执行sql
     * @param $sql
     * @return int
     * @author start
     */
    public function execSql($sql)
    {
        $result= $this->link->exec($sql);
        $this->getPDOError();
        return $result;
    }

    /**
     * 开始事务
     * @author start
     */
    public function beginTransaction()
    {
        $this->link->beginTransaction();
    }

    /**
     * 提交事务
     * @author start
     */
    public function commit()
    {
        $this->link->commit();
    }

    /**
     * 回滚事务
     * @author start
     */
    public function rollback()
    {
        $this->link->rollBack();
    }

    /**
     * 获取表引擎
     * @param $dbName
     * @param $table
     * @return mixed
     * @author start
     */
    public function getTableEngine($dbName, $table)
    {
        $sql = 'SHOW TABLE STATUS FROM ' . $dbName . ' WHERE Name='. $table;
        $arrayTableInfo = $this->query($sql);
        $this->getPDOError();
        return $arrayTableInfo[0]['Engine'];
    }

    /**
     * 通过事务提交多条sql
     * 先用getTableEngine判断是否支持事务
     * @param $sqlArray
     * @return bool
     * @author start
     */
    public function execTransaction($sqlArray)
    {
        $ret = 1;
        $this->beginTransaction();
        foreach ($sqlArray as $sql) {
            if(!$this->execSql($sql)) $ret = 0;
        }
        if($ret == 0){
            $this->rollback();
            return false;
        } else {
            $this->commit();
            return true;
        }
    }

    /**
     * 检查字段是否存在
     * @param $table
     * @param $dataFields
     * @author start
     */
    private function checkFields($table, $dataFields)
    {
        $fields = $this->getFields($table);
        foreach($dataFields as $key => $value){
            if(!in_array($key, $fields)){
                $this->showError('Unknown column ' . $key . ' in table : ' . $table);
            }
        }
    }

    /**
     * 获取数据表中全部字段
     * @param $table
     * @return array
     * @author start
     */
    private function getFields($table)
    {
        $fields = array();
        $recordSet = $this->link->query('SHOW COLUMNS FROM '. $table);
        $this->getPDOError();
        $recordSet->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $recordSet->fetchAll();
        foreach ($result as $item) {
            $fields[] = $item['Field'];
        }
        return $fields;
    }

    /**
     * 捕获PDO错误信息
     * @throws Exception
     * @author start
     */
    private function getPDOError()
    {
        if($this->link->errorCode() != '00000'){
            $error = $this->link->errorInfo();
            $this->showError($error[2]);
        }
    }

    /**
     * 输出错误信息
     * @param $error
     * @throws Exception
     * @author start
     */
    private function showError($error)
    {
        throw new Exception('showError: '. $error);
    }

    /**
     * 关闭数据库
     */
    public function __destruct()
    {
        $this->link = null;
    }

}