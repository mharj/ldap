<?php

class LdapEntries implements \Iterator {
	private $ds;
	private $sr;
	private $entry = null;
	private $softException=null;
	
	public function __construct ($ds,$sr,$softException=null) {
		$this->ds = $ds;
		$this->sr = $sr;
		$this->softException = $softException;
	}
	
	public function __destruct() {
		if ( $this->softException != null && $this->softException instanceof \Exception ) {
			throw new $this->softException;
		}
	}
	
	public function length () {
		return ldap_count_entries($this->ds,$this->sr);
	}
	
	// Iterator implementation   
	public function rewind () {
        $this->entry = ldap_first_entry($this->ds,$this->sr);
    }
	
    public function current () {
		$obj = new \stdClass();	// new empty object
		$obj->dn = ldap_get_dn($this->ds,$this->entry);	// attach dn
		$attrs = ldap_get_attributes($this->ds, $this->entry);	// attach attributes
		for ( $i=0;$i<$attrs['count'];$i++) {
			unset($attrs[$i]);
		}
		unset($attrs['count']);
		foreach ( $attrs AS $key => $a ) {
			$key=preg_replace("/\;/","_",$key); // change ';' => '_' as ';' is not valid in object key name 
			$kname = strtolower($key);
			$obj->$kname=$a;
		}
		return $obj;		
    }
	
	public function key () {
        return ldap_get_dn($this->ds,$this->entry);
    }
	
    public function next () {
		$this->entry = ldap_next_entry($this->ds,$this->entry);
    }
	
    public function valid () {
        return ( $this->entry != null );
    }
}
