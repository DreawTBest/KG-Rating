<?php
	class DreawDB {

	/**
	*	Přístupový údaj, hostitelský server databáze
	*/
    private $_host 	= DB_HOST;

    /**
	*	Přístupový údaj, přihlašovací jméno databáze
	*/
	private $_user 	= DB_USER;

	/**
	*	Přístupový údaj, přihlašovací heslo databáze
	*/
	private $_pass 	= DB_PASS;

	/**
	*	Přístupový údaj, název databáze
	*/
	private $_dbname = DB_NAME;

	/**
	*	Přístupový bod pro PDO připojení, prostředník mezi prostředím a PDO
	*	DatabaseHandler
	*/
	private $_dbh;

	/**
	*	Výsledek nebo také stav relací a operací
	*	Statement
	*/
	private $_stmt;

	/**
	*	Připojení k databázi
	*	@param none
	*	@return none
	*/
	public function __construct() {
			$dsn = 'mysql:host=' . $this->_host . ';dbname=' . $this->_dbname . ';charset=utf8';
			$options = array(
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
		);

		try {
			$this->_dbh = new PDO($dsn, $this->_user, $this->_pass, $options);
		} catch (PDOException $e) {
			throw new ErrorHandler($e->getMessage());
		}
	}

	/**
	*	Připravení dotazu pro odeslání
	*	@param string $query
	*	@return none
	*/
	public function query($query) {
	    $this->_stmt = $this->_dbh->prepare($query);
	}

	/**
	*	Bindování hodnot na základě parametru či pole
	*	Pokud je první parametr pole, proběhne cyklus pro volání této metody
	*	Pokud první parametr pole není, nabinduje se daná hodnota parametru prvního z parametru druhého
	*	Nepovinný parametr úrčuje typ hodnoty, avšak umí jej rozpoznat také sám
	*	@param string || array $param
	*	@param string [$value]
	*	@param // [$type]
	*	@return none
	*/
	public function bind($param, $value = null, $type = null) {
		if(!is_array($param)) {
			if (is_null($type)) {
				switch (true) {
					case is_int($value): 	$type = PDO::PARAM_INT;
											break;
					case is_bool($value):	$type = PDO::PARAM_BOOL;
											break;
					case is_null($value):	$type = PDO::PARAM_NULL;
											break;
					default:				$type = PDO::PARAM_STR;
											break;
				}
			}
			$this->_stmt->bindValue(':'.$param, $value, $type);
		} else {
			foreach($param as $key => $value) {
				$this->bind($key, $value);
			}
		}
	}

	/**
	*	Provedení daného dotazu
	*	@param none
	*	@return object
	*/
	public function execute() {
	    return $this->_stmt->execute();
	}

	/**
	*	Vrátí pole všech výsledků
	*	@param none
	*	@return array
	*/
	public function fetchAll() {
	    $this->execute();
	    return $this->_stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	*	Vrátí object všech výsledků
	*	@param none
	*	@return object
	*/
	public function fetchObject() {
		$this->execute();
	    return $this->_stmt->fetchAll(PDO::FETCH_OBJ);
	}

	/**
	*	Vrátí ...
	*	@param none
	*	@return array
	*/
	public function fetchColumn() {
		$this->execute();
		return $this->_stmt->fetchColumn();
	}

	/**
	*	Vrátí pole prvního výsledku
	*	@param none
	*	@return array
	*/
	public function fetch() {
		$this->execute();
		return $this->_stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	*	Vrátí počet řádku výsledku
	*	@param none
	*	@return integer
	*/
	public function numRows() {
		$this->execute();
		return count($this->_stmt->fetchAll(PDO::FETCH_ASSOC));
	}

	/**
	*	Vrátí počet ovlivněných řádků příkazy - INSERT, DELETE, UPDATE
	*	@param none
	*	@return integer
	*/
	public function rowCount(){
		return $this->_stmt->rowCount();
	}

	/**
	*	Vrátí ID posledního vloženého záznamu
	*	@param none
	*	@return integer
	*/
	public function lastInsertId(){
		return $this->_dbh->lastInsertId();
	}

	/**
	*	Spustí tzv. transakci, blok bezpečného přerušení
	*	@param none
	*	@return boolean
	*/
	public function beginTransaction(){
		return $this->_dbh->beginTransaction();
	}

	/**
	*	Ukončí tzv. transakci, blok bezpečného přerušení
	*	@param none
	*	@return boolean
	*/
	public function endTransaction(){
		return $this->_dbh->commit();
	}

	/**
	*	Vrátí provedené operace z tzv. transakce, bloku bezpečných přerušení
	*	@param none
	*	@return boolean
	*/
	public function cancelTransaction(){
	    return $this->_dbh->rollBack();
	}

	/**
	*	Vrátí dostupné informace o připravovaném dotazu
	*	@param none
	*	@return boolean
	*/
	public function debugDump(){
		return $this->_stmt->debugDumpParams();
	}

}
?>