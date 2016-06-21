<?php
namespace mharj;

class LdapAttribute implements \ArrayAccess,\Countable  {
	private $values;
	private $name;
	private $keys = array();
	private $crc;
	public function __construct(string $name,$values=null) {
		$this->crc = crc32(strtolower($name));
		if ( preg_match("/;/",$name ) ) { // multipart name
			$parts = explode(";",$name);
			$name = array_shift($parts);
			foreach($parts AS $key ) {
				$this->keys[]=strtolower(trim($key));
			}
		}
		$this->name = $name;
		if ( is_null($values) ) {
			$this->values = null;
		} else {
			$this->values = (is_array($values)?$values:array($values));
		}
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getValues() {
		return $this->values;
	}
	public function getKeys() {
		return $this->keys;
	}
	public function getHash() {
		return $this->crc;
	}
	// Array Access functions = []
	public function offsetExists($offset) {
		return isset($this->values[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->values[$offset]) ? $this->values[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
	}

	public function offsetUnset($offset) {
		unset($this->values[$offset]);
	}
	// Countable
	public function count($mode = 'COUNT_NORMAL') {
		return count($this->values);
	}
	
}
