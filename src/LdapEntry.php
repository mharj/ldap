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
	  $this->attributes['dn'] = new LdapAttribute("dn",$dn);
  }
  public function getDn() {
	  return $this->dn;
  }
  public function __get($name) {
	  echo "call __get ".$name."\n";
	  if ( $name == "dn" ) {
		  trigger_error("LdapEntry->dn is deprecated, use LdapEntry->getDN()",E_USER_DEPRECATED);
		  return $this->dn;
	  }
	  return $this->attributes[$name];
  }

  public function __set($name,$value) {
	  echo "call __set ".$name." = ".json_encode($value)."\n";
	  $this->data[$name] = $value;
  }
  public function setAttribute(LdapAttribute $attr) {
	  $this->attributes[$attr->getName()]=$attr;
  }
  
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
