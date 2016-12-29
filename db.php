<?php
/**
 * Доступ к базе данных через PDO.  
 * @version 1.1
 */
class DB
{
	/**
	 * Объект PDO.
	 */
	public static $dbh = null;

	/**
	 * "Statement Handle".
	 */
	public static $sth = null;

	/**
	 * Выполняемый SQL запрос.
	 */
	public static $query = '';

	/**
	 * Подключение к БД
	 * 
	 * @return object
	 */
	public static function getDbh()
	{	
		if (!self::$dbh) {
			try {
				self::$dbh = new PDO(Config::$db_dsn, Config::$db_user, Config::$db_pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			} catch (PDOException $e) {
				exit('Error connecting to database: ' . $e->getMessage());
			}
		}

		return self::$dbh; 
	}

	/**
	 * Подготовка SQL запроса.
	 */
	private static function _prepareQuery($query)
	{
		return self::$query = str_replace('#__', Config::$db_prefix, $query);
	}

	/**
	 * Получение ошибки запросса.
	 */
	public static function getError()
	{
		$info = self::$sth->errorInfo();
		
		return (isset($info[2])) ? 'SQL: ' . $info[2] : null;
	}
	
	/**
	 * Возвращает структуру таблицы в виде асативного массива.
	 */
	public static function getStructure($table)
	{
		$res = array();
		foreach (self::getAll("SHOW COLUMNS FROM {$table}") as $row) {
			$res[$row['Field']] = (is_null($row['Default'])) ? '' : $row['Default'];
		}

		return $res;
	}

	/**
	 * Добовление в таблицу, в случаи успеха вернет вставленный ID, иначе 0.
	 */
	public static function add($query, $param = array())
	{
		if (!is_null($query)) {
			self::$sth = self::getDbh()->prepare(self::_prepareQuery($query));
		}
		
		return (self::$sth->execute((array) $param)) ? self::getDbh()->lastInsertId() : 0;
	}
	
	/**
	 * Выполнение запроса.
	 */
	public static function set($query, $param = array())
	{
		if (!is_null($query)) {
			self::$sth = self::getDbh()->prepare(self::_prepareQuery($query));
		}

		return self::$sth->execute((array) $param);
	}
	
	/**
	 * Получение строки из таблицы.
	 */
	public static function getRow($query, $param = array(), $mode = PDO::FETCH_ASSOC)
	{
		if (!is_null($query)) {
			self::$sth = self::getDbh()->prepare(self::_prepareQuery($query));
		}
		
		self::$sth->execute((array) $param);

		return self::$sth->fetch($mode);		
	}
	
	/**
	 * Получение всех строк из таблицы.
	 */
	public static function getAll($query, $param = array(), $mode = PDO::FETCH_ASSOC)
	{
		if (!is_null($query)) {
			self::$sth = self::getDbh()->prepare(self::_prepareQuery($query));
		}

		self::$sth->execute((array) $param);

		return self::$sth->fetchAll($mode);	
	}

	/**
	 * Возвращает потомков из таблицы.
	 */
	public static function getChilds($sql, $param = array(), $max_livel = 0)
	{
		if ($items = self::getAll($sql, $param)) {
			foreach ($items as $row) {
				$res[] = array_merge($row, array('livel' => 1));
				self::getChildsNode($res, $sql, $row['id'], $max_livel, 1);
			}

			return $res;
		} else {
			return array();
		}
	}
	
	/**
	 * Внутренняя рекурсивная функция
	 */
	public static function getChildsNode(&$res, $sql, $param, $max_livel, $livel)
	{
		$livel++;
		if (empty($max_livel) || $livel <= $max_livel) {
			if ($items = self::getAll($sql, $param)) {
				foreach ($items as $row) {
					$res[] = array_merge($row, array('livel' => $livel));
					self::getChildsNode($res, $sql, $row['id'], $max_livel, $livel);
				}
			}
		}
	}		

	/**
	 * Получение значения.
	 */
	public static function getValue($query, $param = array(), $default = null)
	{
		$result = self::getRow($query, $param);
		if (!empty($result)) {
			$result = array_shift($result);
		}

		return (empty($result)) ? $default : $result;	
	}
	
	/**
	 * Получение столбца таблицы.
	 */
	public static function getColumn($query, $param = array())
	{
		if (!is_null($query)) {
			self::$sth = self::getDbh()->prepare(self::_prepareQuery($query));
		}

		self::$sth->execute((array) $param);

		return self::$sth->fetchAll(PDO::FETCH_COLUMN);	
	}
}