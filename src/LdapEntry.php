<?php
namespace mharj;

class LdapEntry extends \stdClass {
  private static $dropInternalAttrs = array('createtimestamp','creatorsname','modifytimestamp','modifiersname');
  
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
        foreach ( $v AS $vk => $vv ) {
          mb_convert_encoding($v,'UTF-8','UTF-8');
          $v[$vk]=$vv;
        }
        sort($v);
        $ret[$k]=$v;
      } else {
        mb_convert_encoding($v,'UTF-8','UTF-8');
        $ret[$k]=$v;
      }
    }
    return $ret;
  }
}
