<?php
namespace mharj;
class LdapQuery {
	public $base;
	public $filter;
	public $attrs;
	public $sizeLimit = 0;
	public $timeLimit = 0;
	public $scope;
	public $deref = LDAP_DEREF_NEVER;
	public function __construct($base=null,$filter=null,$attrs=array('*'),$scope=LDAP_SCOPE_SUBTREE) {
		$this->base = $base;
		$this->filter = $filter;
		$this->attrs = $attrs;
		$this->scope = $scope;
	}
}
