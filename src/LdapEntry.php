<?php
namespace mharj;

class LdapEntry extends \stdClass {
	private static $dropInternalAttrs = array('createtimestamp','creatorsname','modifytimestamp','modifiersname');
	private $data;
	private $attributes;
	private $dn;

	public function __construct($dn=null) {
		$this->attributes = array();
		$this->dn = $dn;
		$this->data['dn']=$dn;
		$dnla = new LdapAttribute("dn",$dn);
		$this->attributes[$dnla->getHash()]=$dnla;
	}
	
	public function getDn() {
		return $this->dn;
	}
	
	public function getAttributes($name,$keys=null) {
		print_r($this->attributes);
		$ret = array();
		foreach ( $this->attributes AS $attr ) {
			if ( $name == $attr->getName() ) {
				if ( $keys == null ) {
					$ret[] = $attr;
				} else { // filter with keys
					$akeys = $attr->getKeys();
					foreach ( $keys AS $k) {
						if ( $k == null && count($akeys) == 0 ) {
							$ret[] = $attr;
						} elseif ( in_array($k,$akeys) ) {
							$ret[] = $attr;
						}
					}
				}
			}
		}
		return $ret;
	}
	
	public function __get($name) {
		if ( $name == "dn" ) {
			trigger_error("LdapEntry->dn is deprecated, use LdapEntry->getDN()",E_USER_DEPRECATED);
			return $this->getDn();
		}
		foreach ( $this->attributes AS $attr ) {
			if ( $name == $attr->getName() ) {
				return $attr;
			}
		}
		return null;
	}

	public function __set($name,LdapAttribute $value) {
		$this->data[] = $value;
	}
	
	// build indexing for fast get
	public function addAttribute(LdapAttribute $attr) {
		$this->attributes[$attr->getHash()]=$attr;
	}

	// TODO: build LdapAttribute support
	public function equals(LdapEntry $entry) {
		$target = $this->sort($this->dropInternal( (array)$entry ) );
		$source = $this->sort($this->dropInternal( (array)$this ) );
		return (json_encode($target)==json_encode($source));
	}
  
	public function debugEquals(LdapEntry $entry) {
		$target = $this->sort($this->dropInternal( (array)$entry ) );
		$source = $this->sort($this->dropInternal( (array)$this ) );
		echo json_encode($target)."\n";
		echo json_encode($source)."\n";
	}  
	
	private function dropInternal(array $a) {
		$ret = $a;
		foreach ( LdapEntry::$dropInternalAttrs AS $attr ) {
			if ( isset($ret[$attr]) ) {
				unset($ret[$attr]);
			}
		}
		return $ret;
	}
  
	private function sort(array $a) {
		$ret = $a;
		ksort($ret);
		foreach ( $ret AS $k => $v ) {
			if ( is_array($v) ) {
				sort($v);
				$ret[$k]=$v;
			}
		}
		return $ret;
	}
}
