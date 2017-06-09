<?php
//数据库
class DB
{

	private static $obj;
	private static $connType;
	private static $connArr = array ();
	private static $dbKey = 'conn1';
	private static $confPath;
	private static $config = array (
		'host'=>'127.0.0.1',
		'port'=>'3306',
		'user'=>'root',
		'pass'=>'123',
		'dbname'=>'test',
		'charset'=>'utf8'
	);
	/**
     * 单例模式
     *
     * @return obj
     */
	public static function obj(){
		if(class_exists("DB")&&!self::$obj){
			self::$obj = new DB();
		}
		return self::$obj;
	}
	/**
     * 构造函数
     *
     * @return string
     */
	private function __construct()
	{
		if (! isset ( $key ))
			$key = self::$dbKey;
		// 判断是否已经选择mysql数据库驱动
		if (! isset ( self::$connType )) {
			if (extension_loaded('pdo_mysql'))
				self::$connType = 1;
			elseif (class_exists ( 'mysqli' ))
				self::$connType = 2;
			elseif (function_exists ( 'mysql_connect' ))
				self::$connType = 3;
			else
			    $this->errmsg = "mysql driver not found ";
		}
		if (! isset ( self::$connArr [$key] )) {
			$config = self::$config;
			$initSql = 'SET NAMES ' . $config ['charset'];
			if (self::$connType == 1) {
				// pdo
				$dsn = sprintf ( 'mysql:host=%s;port=%d;dbname=%s', $config ['host'], $config ['port'], $config ['dbname'] );
				try {
					$conn = new PDO ( $dsn, $config ['user'], $config ['pass'] );
					$conn->exec ( $initSql );
					self::$connArr [$key] = $conn;
				} catch ( Exception $e ) {
				    $this->errmsg = 'connect error to key@' . $key . ',' . $e->getMessage ();
				}
			} elseif (self::$connType == 2) {
				// mysqli
				$db = @new mysqli ( $config ['host'], $config ['user'], $config ['pass'], $config ['dbname'], $config ['port'] );
				if ($db->connect_error) {
					$this->errmsg = 'connect error to key@' . $key . ', (' . $db->connect_errno . ') ' . $db->connect_error ;
				}
				$db->query ( $initSql );
				self::$connArr [$key] = $db;
			} elseif (self::$connType == 3) {
				// mysql
				$link = @mysql_connect ( $config ['host'] . ':' . $config ['port'], $config ['user'], $config ['pass'] );
				if (! $link) {
					$this->errmsg = 'connect error to key@' . $key . ', ' . mysql_error () ;
				}
				mysql_select_db ( $config ['dbname'] );
				mysql_query ( $initSql, $link );
				self::$connArr [$key] = $link;
			}
		}
	}
	/**
	 * 选择一个链接源
	 *
	 * @param string $key
	 *        	数据库配置的连接源键
	 * @return void
	 */
	public static function selectDbKey($key) {
		self::$dbKey = $key;
	}
	/**
	 * 设置配置文件路径
	 *
	 * @param string $fpath
	 *        	数据库配置文件路径
	 * @return void
	 */
	public static function setConfPath($fpath) {
		self::$confPath = $fpath;
	}
	/**
	 * 执行一条sql语句,返回受影响的行数
	 *
	 * @param string $sql
	 *        	sql语句
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @return int 返回受影响的行数,失败则返回false
	 */
	public static function exec($sql, $key = null) {
		if (! isset ( $key ))
			$key = self::$dbKey;
		$conn = self::$connArr [$key];
		if (self::$connType == 1) {
			return $conn->exec ( $sql );
		} elseif (self::$connType == 2) {
			$result = $conn->query ( $sql );
			if ($result)
				return $conn->affected_rows;
			else
				return false;
		} elseif (self::$connType == 3) {
			$result = mysql_query ( $sql, $conn );
			if ($result)
				return mysql_affected_rows ( $conn );
			else
				return false;
		}
	}
	/**
	 * 执行一条sql语句,返回结果集对象
	 *
	 * @param string $sql
	 *        	sql语句
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @return QueryStatement 返回结果集对象,如果失败则返回false
	 */
	public function query($sql, $key = null) {
		if (! isset ( $key ))
			$key = self::$dbKey;
		$conn = self::$connArr [$key];
		if (self::$connType == 1) {
			$stm=$conn->query ( $sql );
			if ($stm === false)
				return false;
			while($row = $stm->fetch()){
				$result [] = $row;
			}
			return $result;
		} elseif (self::$connType == 2) {
			$stm = $conn->query ( $sql );
			if ($stm === false)
				return false;
			while ( $row = $stm->fetch_array () ) {
				$result [] = $row;
			}
			if (! empty ( $result ))
				return $result;
			else
				return null;
		} elseif (self::$connType == 3) {
			$stm = mysql_query ( $sql, $conn );
			if ($stm === false)
				return false;
			$result = array ();
			while ( $row = mysql_fetch_array ( $stm ) ) {
				$result [] = $row;
			}
			if (! empty ( $result ))
				return $result;
			else
				return null;
		}
	}
	/**
	 * 插入一条记录的快捷语句
	 *
	 * @param string $tableName
	 *        	表名
	 * @param array $data
	 *        	数据数组
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @example <code>DbConnection::insert('user',
	 *          array(
	 *          'username'=>'user1',
	 *          'email'=>'user1@test.com'
	 *          ), 'main');</code>
	 * @return int 返回受影响的行数,失败则返回false
	 */
	public function insert($tableName, $data, $key = null) {
	    $arrKeys=array_keys ( $data );
	    foreach ($arrKeys as $k=>$v){
	        $arrKeys[$k]='`'.$v.'`';
	    }
		$sql = 'INSERT INTO ' . $tableName . ' (' . implode ( ',', $arrKeys ) . ') VALUES (';
		foreach ( $data as $k => $v ) {
			if (is_string ( $v ))
				$data [$k] = '\'' . addslashes ( $v ) . '\'';
			elseif ($v === null)
				$data [$k] = 'NULL';
		}
		$sql .= (implode ( ',', $data ) . ')');
		return self::exec ( $sql, $key );
	}
	/**
	 * 向一张表中插入多条记录的快捷语句
	 *
	 * @param string $tableName
	 *        	表名
	 * @param array $data
	 *        	数据数组
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @example <code>DbConnection::insertArrs('user',
	 *          array(
	 *          array(
	 *          'username'=>'user1',
	 *          'email'=>'user1@test.com'
	 *          ),
	 *          array(
	 *          'username'=>'user2',
	 *          'email'=>'user2@test.com'
	 *          )
	 *          ), 'main');</code>
	 * @return int 返回受影响的行数,失败则返回false
	 */
	public function insertArrs($tableName, $dataArrs, $key = null) {
		$data = current ( $dataArrs );
		$sql = 'INSERT INTO ' . $tableName . ' (' . implode ( ',', array_keys ( $data ) ) . ') VALUES ';
		$queryArrs = array ();
		foreach ( $dataArrs as $data ) {
			foreach ( $data as $k => $v ) {
				if (is_string ( $v ))
					$data [$k] = '\'' . addslashes ( $v ) . '\'';
				elseif ($v === null)
					$data [$k] = 'NULL';
			}
			$queryArrs [] = '(' . implode ( ',', $data ) . ')';
		}
		$sql .= implode ( ',', $queryArrs );
		return self::exec ( $sql, $key );
	}
	/**
	 * 插入一条记录的快捷语句
	 *
	 * @param string $tableName
	 *        	表名
	 * @param array $data
	 *        	数据数组
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @example <code>DbConnection::insert('user',
	 *          array(
	 *          'username'=>'user1',
	 *          'email'=>'user1@test.com'
	 *          ), 'WHERE id=2');</code>
	 * @return int 返回受影响的行数,失败则返回false
	 */
	public function update($tableName, $data, $whereStr = '', $key = null) {
		$sql = 'UPDATE ' . $tableName . ' SET ';
		$updateArr = array ();
		foreach ( $data as $k => $v ) {
			if (is_string ( $v ))
				$updateArr [] = $k . '=\'' . addslashes ( $v ) . '\'';
			elseif ($v === null)
				$updateArr [] = $k . '=NULL';
			else
				$updateArr [] = $k . '=' . $v;
		}
		$sql .= implode ( ',', $updateArr );
		if (! empty ( $whereStr ))
			$sql .= (' ' . $whereStr);
		return self::exec ( $sql, $key );
	}
	/**
	 * 获取最后一次的错误信息
	 *
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @return string
	 */
	public function getErrorMsg($key = null) {
		if (! isset ( $key ))
			$key = self::$dbKey;
		$conn = self::$connArr [$key];
		if (self::$connType == 1) {
			$errorInfo = $conn->errorInfo ();
			return $errorInfo [2];
		} elseif (self::$connType == 2) {
			return $conn->error;
		} elseif (self::$connType == 3) {
			return mysql_error ( $conn );
		}
	}
	/**
	 * 返回最后插入行的ID或序列值
	 *
	 * @param string $key
	 *        	连接源,如果不指定,则使用选择的源
	 * @return string
	 */
	public function getLastId($key = null) {
		if (! isset ( $key ))
			$key = self::$dbKey;
		$conn = self::$connArr [$key];
		if (self::$connType == 1) {
			return $conn->lastInsertId ();
		} elseif (self::$connType == 2) {
			return $conn->insert_id;
		} elseif (self::$connType == 3) {
			return mysql_insert_id ( $conn );
		}
	}
}
?>