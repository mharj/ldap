<?php
namespace mharj;
defined('LDAP_PROXIED_CONTROL') or define('LDAP_PROXIED_CONTROL', '2.16.840.1.113730.3.4.18');
defined('LDAP_SCOPE_BASE') or define('LDAP_SCOPE_BASE', 0);
defined('LDAP_SCOPE_ONELEVEL') or define('LDAP_SCOPE_ONELEVEL', 1);
defined('LDAP_SCOPE_SUBTREE') or define('LDAP_SCOPE_SUBTREE',2);

class Ldap {
	public $ds = null;
	private $lastException = null;
	private $eh;
	
	public function __construct (string $obj,int $port=389) {
		$this->eh = array(&$this, 'log_ldap_errors');
		if ( is_string($obj) ) { // new connection
			set_error_handler( $this->eh );
			$this->ds = ldap_connect($obj,$port);
			restore_error_handler();
		} else if ( is_resource($obj) == true  && get_resource_type($obj) == 'ldap link' ) { // reuse connection
			$this->ds = &$obj;
		} else {
			throw new LdapException("Invalid parameter");
		}
	}
	
	public function __destruct () {
		$this->close();
	}
	
	public function bind ($dn=null,$password=null) {
		try {
			set_error_handler( $this->eh );
			ldap_bind($this->ds,$dn,$password);
		} catch( LdapNotFoundException $e) {
			throw new LdapBindException($e->getMessage(),$e->getCode());
		} finally {
			restore_error_handler();
		}
	}
	
	
	public function setProxyDN($proxydn) {
		// check LDAP protocol is at least 3 for proxy
		if ( $this->getOption(LDAP_OPT_PROTOCOL_VERSION) < 3 ) {
			throw new LdapException("Could not set Proxy Auth (Protocol = ".$this->getOption(LDAP_OPT_PROTOCOL_VERSION)." )");	
		}
		// check proxy feature
		$proxyFeature = array('oid' => LDAP_PROXIED_CONTROL,'iscritical' => true,'value' => 'dn:'.$proxydn);
		set_error_handler( $this->eh );
		if ( ! ldap_set_option($this->ds, LDAP_OPT_SERVER_CONTROLS, array($proxyFeature) ) ) {
			restore_error_handler();
			throw new LdapException("Could not set Proxy Auth");
		}
		restore_error_handler();
	}
	
	
	public function query(LdapQuery $query): LdapEntries {
		set_error_handler( $this->eh );
		switch( $query->scope ) {
			case LDAP_SCOPE_SUBTREE:
				$sr = ldap_search($this->ds,$query->base,$query->filter,$query->attrs,0,$query->sizeLimit,$query->timeLimit,$query->deref);
				break;
			case LDAP_SCOPE_ONELEVEL:
				$sr = ldap_list($this->ds,$query->base,$query->filter,$query->attrs,0,$query->sizeLimit,$query->timeLimit,$query->deref);
				break;
			case LDAP_SCOPE_BASE:
				$sr = ldap_read($this->ds,$query->base,$query->filter,$query->attrs,0,$query->sizeLimit,$query->timeLimit,$query->deref);
				break; 
			default:
				throw new LdapException("Unknown query scope");
		}
		restore_error_handler();
		return new LdapEntries($this->ds,$sr);
	}
	
	public function search ($base,$filer,$attrs): LdapEntries {
		set_error_handler( $this->eh );
		$sr = ldap_search($this->ds,$base,$filer,$attrs);
		if ( $sr == false ) {
			restore_error_handler();
			throw new LdapException(ldap_error($this->ds));
		}
		restore_error_handler();
		return new LdapEntries($this->ds,$sr,$this->lastException);
	}
	
	public function read ($base,$filer,$attrs): LdapEntries {
		set_error_handler( $this->eh );
		$sr = ldap_read($this->ds,$base,$filer,$attrs);
		restore_error_handler();
		return new LdapEntries($this->ds,$sr);
	}

	public function setOption ($option,$value) {
		set_error_handler( $this->eh );
		ldap_set_option($this->ds,$option,$value);
		restore_error_handler();
	}
	
	public function getOption ($option) {
		$value = null;
		set_error_handler( $this->eh );
		ldap_get_option($this->ds,$option,$value);
		restore_error_handler();
		return $value;
	}	
	
	public function close () {
		if ( is_resource($this->ds) ) {
		 	ldap_close($this->ds);
		}
	}
	
	// main add/rename/delete methods
	public function add ($dn , $attributes) {
		set_error_handler( $this->eh );
		ldap_add($this->ds,$dn,$attributes);
		restore_error_handler();
	}
	
	public function delete ($dn) {
		set_error_handler( $this->eh );
		ldap_delete($this->ds,$dn);
		restore_error_handler();
	}
	public function rename ($dn,$rdn,$parent=null,$delete=true) {
		set_error_handler( $this->eh );
		ldap_rename($this->ds,$dn,$rdn,$parent,$delete);
		restore_error_handler();
	}
	
	// modify methods (wrapper)
	public function modAdd($dn,$mod) {
		set_error_handler( $this->eh );
		ldap_mod_add($this->ds,$dn,$mod);
		restore_error_handler();
	}
	
	public function modReplace($dn,$mod) {
		set_error_handler( $this->eh );
		ldap_mod_replace($this->ds,$dn,$mod);
		restore_error_handler();
	}
	
	public function modDel($dn,$mod) {
		set_error_handler( $this->eh );
		ldap_mod_del($this->ds,$dn,$mod);
		restore_error_handler();
	}
	
	// LDAP error wrapper
	private function log_ldap_errors ($num,$str) {
		$num = ldap_errno($this->ds); // get LDAP error code instead
		switch ( ldap_errno($this->ds) ) {
			case 0x04:	$this->lastException = new LdapSizeException( $str, $num ,null );	// LDAP_SIZELIMIT_EXCEEDED
						break;
			case 0x0b:	$this->lastException = new LdapSizeException( $str, $num ,null );	// LDAP_ADMINLIMIT_EXCEEDED
						break;
			case 0x44:	throw new LdapAlreadyExistsException( $str, $num ,null );			// LDAP_ALREADY_EXISTS
			case 0x31:	throw new LdapBindException( $str, $num ,null );				// LDAP_INVALID_CREDENTIALS
			case 0x32:	throw new LdapPermissionException( $str, $num ,null );				// LDAP_INSUFFICIENT_ACCESS        
			case 0x20:	throw new LdapNotFoundException( $str, $num ,null );				// LDAP_NO_SUCH_OBJECT
			default:	throw new LdapException( $str, $num ,null );
		}
	}
	public static function getTimestampAsDateTime(string $date): \DateTime {
		return \DateTime::createFromFormat("YmdGise",$date);
	}
}

