<?php

class LdapQuery {
	public $base;
	public $filter;
	public $attrs;
	public $sort;
	public $sizeLimit = 0;
	public $timeLimit = 0;
	public $deref = LDAP_DEREF_NEVER;
	public $scope = LDAP_SCOPE_SUBTREE; // LDAP_SCOPE_SUBTREE | LDAP_SCOPE_ONELEVEL | LDAP_SCOPE_BASE
	public function __construct($base=null,$filter=null,$attrs=array('*'),$sort=null) {
		$this->base = $base;
		$this->filter = $filter;
		$this->attrs = $attrs;
		$this->sort = $sort;
	}
}
