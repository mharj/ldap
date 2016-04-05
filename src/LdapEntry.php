<?php
namespace mharj;

class LdapEntry extends \stdClass {
  private static $dropInternalAttrs = array('createtimestamp','creatorsname','modifytimestamp','modifiersname');
  
  public function equals(LdapEntry $entry) {
    $target = $this->sort($this->dropInternal( (array)$entry ) );
    $source = $this->sort($this->dropInternal( (array)$this ) );
    return (json_encode($target)==json_encode($source));
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
