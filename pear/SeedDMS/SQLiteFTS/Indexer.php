<?php
/**
 * Implementation of SQLiteFTS index
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: 1.0.16
 */


/**
 * Class for managing a SQLiteFTS index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: 1.0.16
 */
class SeedDMS_SQLiteFTS_Indexer {

	/**
	 * @var string $ftstype
	 * @access protected
	 */
	protected $_ftstype;

	/**
	 * @var object $index sqlite index
	 * @access protected
	 */
	protected $_conn;

	const ftstype = 'fts5';
	/**
	 * Constructor
	 *
	 */
	function __construct($indexerDir) { /* {{{ */
		$this->_conn = new PDO('sqlite:'.$indexerDir.'/index.db');
		$this->_ftstype = self::ftstype;
		if($this->_ftstype == 'fts5')
			$this->_rawid = 'rowid';
		else
			$this->_rawid = 'docid';
	} /* }}} */

	/**
	 * Open an existing index
	 *
	 * @param string $indexerDir directory on disk containing the index
	 */
	static function open($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db')) {
			return new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
		} else
			return self::create($conf);
	} /* }}} */

	/**
	 * Create a new index
	 *
	 * @param array $conf $conf['indexdir'] is the directory on disk containing the index
	 */
	static function create($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db'))
			unlink($conf['indexdir'].'/index.db');
		$index =  new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
		/* Make sure the sequence of fields is identical to the field list
		 * in SeedDMS_SQLiteFTS_Term
		 */
		$version = SQLite3::version();
		if(self::ftstype == 'fts4') {
			if($version['versionNumber'] >= 3008000)
				$sql = 'CREATE VIRTUAL TABLE docs USING fts4(documentid, title, comment, keywords, category, mimetype, origfilename, owner, content, created, users, status, path, notindexed=created, matchinfo=fts3)';
			else
				$sql = 'CREATE VIRTUAL TABLE docs USING fts4(documentid, title, comment, keywords, category, mimetype, origfilename, owner, content, created, users, status, path, matchinfo=fts3)';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
			$sql = 'CREATE VIRTUAL TABLE docs_terms USING fts4aux(docs);';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
		} elseif(self::ftstype == 'fts5') {
			$sql = 'CREATE VIRTUAL TABLE docs USING fts5(documentid, title, comment, keywords, category, mimetype, origfilename, owner, content, created unindexed, users, status, path)';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
			$sql = 'CREATE VIRTUAL TABLE docs_terms USING fts5vocab(docs, \'col\');';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
		} else
			return null;
		return($index);
	} /* }}} */

	/**
	 * Do some initialization
	 *
	 */
	static function init($stopWordsFile='') { /* {{{ */
	} /* }}} */

	/**
	 * Add document to index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_SQLiteFTS_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	function addDocument($doc) { /* {{{ */
		if(!$this->_conn)
			return false;

		foreach(array('comment', 'keywords', 'category', 'content', 'mimetype', 'origfilename', 'status', 'created') as $kk) {
			try {
				${$kk} = $doc->getFieldValue($kk);
			} catch (Exception $e) {
				${$kk} = '';
			}
		}
		$sql = "INSERT INTO docs (documentid, title, comment, keywords, category, owner, content, mimetype, origfilename, created, users, status, path) VALUES (".$this->_conn->quote($doc->getFieldValue('document_id')).", ".$this->_conn->quote($doc->getFieldValue('title')).", ".$this->_conn->quote($comment).", ".$this->_conn->quote($keywords).", ".$this->_conn->quote($category).", ".$this->_conn->quote($doc->getFieldValue('owner')).", ".$this->_conn->quote($content).", ".$this->_conn->quote($mimetype).", ".$this->_conn->quote($origfilename).", ".(int)$created.", ".$this->_conn->quote($doc->getFieldValue('users')).", ".$this->_conn->quote($status).", ".$this->_conn->quote($doc->getFieldValue('path'))/*time()*/.")";
		$res = $this->_conn->exec($sql);
		if($res === false) {
			return false;
			var_dump($this->_conn->errorInfo());
		}
		return $res;
	} /* }}} */

	/**
	 * Remove document from index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_SQLiteFTS_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	public function delete($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "DELETE FROM docs WHERE ".$this->_rawid."=".(int) $id;
		$res = $this->_conn->exec($sql);
		return $res;
	} /* }}} */

	/**
	 * Check if document was deleted
	 *
	 * Just for compatibility with lucene.
	 *
	 * @return boolean always false
	 */
	public function isDeleted($id) { /* {{{ */
		return false;
	} /* }}} */

	/**
	 * Find documents in index
	 *
	 * @param string $query 
	 * @param array $limit array with elements 'limit' and 'offset'
	 * @return boolean false in case of an error, otherwise array with elements
	 * 'count', 'hits', 'facets'. 'hits' is an array of SeedDMS_SQLiteFTS_QueryHit
	 */
	public function find($query, $limit=array()) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT count(*) AS `c` FROM `docs`";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		$res = $this->_conn->query($sql);
		if(!$res)
			return false;
		$row = $res->fetch();

		$sql = "SELECT ".$this->_rawid.", documentid FROM docs";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		if($this->_ftstype == 'fts5')
			//$sql .= " ORDER BY rank";
			// boost documentid, title and comment
			$sql .= " ORDER BY bm25(docs, 10.0, 10.0, 10.0)";
		if(!empty($limit['limit']))
			$sql .= " LIMIT ".(int) $limit['limit'];
		if(!empty($limit['offset']))
			$sql .= " OFFSET ".(int) $limit['offset'];
		$res = $this->_conn->query($sql);
		$hits = array();
		if($res) {
			foreach($res as $rec) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec[$this->_rawid];
				$hit->documentid = $rec['documentid'];
				$hits[] = $hit;
			}
		}
		return array('count'=>$row['c'], 'hits'=>$hits);
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param string $id id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function findById($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT ".$this->_rawid.", documentid FROM docs WHERE documentid=".$this->_conn->quote($id);
		$res = $this->_conn->query($sql);
		$hits = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec[$this->_rawid];
				$hit->documentid = $rec['documentid'];
				$hits[] = $hit;
			}
		}
		return $hits;
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param integer $id id of index record
	 * @return boolean false in case of an error, otherwise true
	 */
	public function getDocument($id, $content=true) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT ".$this->_rawid.", documentid, title, comment, owner, keywords, category, mimetype, origfilename, created, users, status, path".($content ? ", content" : "")." FROM docs WHERE ".$this->_rawid."='".$id."'";
		$res = $this->_conn->query($sql);
		$doc = false;
		if($res) {
			if(!($rec = $res->fetch(PDO::FETCH_ASSOC)))
				return false;
			$doc = new SeedDMS_SQLiteFTS_Document();
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('docid', $rec[$this->_rawid]));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('document_id', $rec['documentid']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('title', $rec['title']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('comment', $rec['comment']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('keywords', $rec['keywords']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('category', $rec['category']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('mimetype', $rec['mimetype']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('origfilename', $rec['origfilename']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('owner', $rec['owner']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('created', $rec['created']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('users', $rec['users']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('status', $rec['status']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('path', $rec['path']));
			if($content)
				$doc->addField(SeedDMS_SQLiteFTS_Field::UnStored('content', $rec['content']));
		}
		return $doc;
	} /* }}} */

	/**
	 * Return list of terms in index
	 *
	 * This function does nothing!
	 */
	public function terms() { /* {{{ */
		if(!$this->_conn)
			return false;

		if($this->_ftstype == 'fts5')
			$sql = "SELECT term, col, doc as occurrences FROM docs_terms WHERE col!='*' ORDER BY col";
		else
			$sql = "SELECT term, col, occurrences FROM docs_terms WHERE col!='*' ORDER BY col";
		$res = $this->_conn->query($sql);
		$terms = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$term = new SeedDMS_SQLiteFTS_Term($rec['term'], $rec['col'], $rec['occurrences']);
				$terms[] = $term;
			}
		}
		return $terms;
	} /* }}} */

	/**
	 * Return list of documents in index
	 *
	 */
	public function count() { /* {{{ */
		$sql = "SELECT count(*) c FROM docs";
		$res = $this->_conn->query($sql);
		if($res) {
			$rec = $res->fetch(PDO::FETCH_ASSOC);
			return $rec['c'];
		}
		return 0;
	} /* }}} */

	/**
	 * Commit changes
	 *
	 * This function does nothing!
	 */
	function commit() { /* {{{ */
	} /* }}} */

	/**
	 * Optimize index
	 *
	 * This function does nothing!
	 */
	function optimize() { /* {{{ */
	} /* }}} */
}
?>
