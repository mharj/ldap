<?php
use mharj\Ldap;
use mharj\LdapEntry;
use mharj\LdapQuery;
use mharj\LdapException;
use mharj\LdapNotFoundException;
use mharj\LdapSizeLimitException;
class LdapTest extends PHPUnit_Framework_TestCase {
	/**
     * @expectedException mharj\LdapException
     */
	public function testNotOkHostname() {
		$ldap = new Ldap("does.not.work",6969);
		$ldap->bind();
	}
	
	/**
     * @expectedException mharj\LdapException
     */
	public function testWrongPort() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],6969);
		$ldap->bind();
	}

	public function testAnonBind() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind();
	}

	public function testAnonBindCopyResource() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind();
                $ldap2 = new Ldap($ldap->ds);
	}        

	public function testAnonBindCopyClass() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind();
                $ldap2 = new Ldap($ldap);
	}        
        
	/**
     * @expectedException mharj\LdapBindException
     */	
	public function testWrongBind() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind("cn=Something Strange","doesnotwork");
	}	
	

	public function testBind3Proto() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->setOption(LDAP_OPT_PROTOCOL_VERSION,3);
		$ldap->bind($ini['ldap']['userdn'],$ini['ldap']['passwd']);
		$this->assertEquals($ldap->getOption(LDAP_OPT_PROTOCOL_VERSION),3);
	}	

	public function testAnonSearch() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind();
		$found = false;
		try {
			foreach ( $ldap->search($ini['ldap']['basedn'],"objectclass=*",array('dn')) AS $e ) {
				$found = true;
			}
		} catch( LdapSizeLimitException $ex) {
			// this is ok
		} catch( LdapException $ex ) {
			if ( $ex->getCode() == 53 ) { // anon access 
				$this->markTestIncomplete(
					'Server: LDAP_UNWILLING_TO_PERFORM on search, maybe anonymous access denied'
				);
			}
		}		
		$this->assertEquals($found, true); // we should have some entries
	}
	
	public function testBindedSearch() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind($ini['ldap']['userdn'],$ini['ldap']['passwd']);
		$found = false;
		try {
			foreach ( $ldap->search($ini['ldap']['basedn'],"objectclass=*",array('dn')) AS $e ) {
				$found = true;
			}
		} catch( LdapSizeLimitException $ex) {
		}
		$this->assertEquals($found, true); // we should have some entries
	}
	
	/**
     * @expectedException mharj\LdapNotFoundException
     */		
	public function testBindedWrongBase() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind($ini['ldap']['userdn'],$ini['ldap']['passwd']);
		$ldap->search("dc=does,dc=not,dc=exists","objectclass=dc",array('dn'));
	}

	public function testBindedReadSelf() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind($ini['ldap']['userdn'],$ini['ldap']['passwd']);
		foreach ( $ldap->read($ini['ldap']['userdn'],"objectclass=*",array('dn')) AS $e ) {
			if ( ! $e instanceof LdapEntry ) {
				throw new Exception("wrong LdapEntry class");
			}
		}
	}
	
	public function testLdapQueryClass() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind($ini['ldap']['userdn'],$ini['ldap']['passwd']);
		
		$found = false;
		try {
			foreach ( $ldap->query(new LdapQuery($ini['ldap']['basedn'],"objectclass=*",array('dn'),LDAP_SCOPE_BASE)) AS $e ) {
				$found = true;
			}
		} catch( LdapSizeLimitException $ex) {}
		$this->assertEquals($found, true); // we should have some entries
			
		$found = false;
		try {
			foreach ( $ldap->query(new LdapQuery($ini['ldap']['basedn'],"objectclass=*",array('dn'),LDAP_SCOPE_ONELEVEL)) AS $e ) {
				$found = true;
			}
		} catch( LdapSizeLimitException $ex) {}
		$this->assertEquals($found, true); // we should have some entries			
			
		$found = false;
		try {			
			foreach ( $ldap->query(new LdapQuery($ini['ldap']['basedn'],"objectclass=*",array('dn'),LDAP_SCOPE_SUBTREE)) AS $e ) {
				$found = true;
			}
		} catch( LdapSizeLimitException $ex) {}			
		$this->assertEquals($found, true); // we should have some entries			
	}
	
	public function testAdminWrite() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->bind($ini['ldap']['admindn'],$ini['ldap']['adminpasswd']);
		list($name)=ldap_explode_dn($ini['ldap']['createoudn'],true);
		// check & remove old test
		try {
			foreach ($ldap->read($ini['ldap']['createoudn'],"objectclass=top",array('dn')) AS $e ) {
				$ldap->delete($ini['ldap']['createoudn']);
			}
		} catch(LdapNotFoundException $ex) {
			// ok
		}
		// 
		$ldap->add($ini['ldap']['createoudn'],array('objectclass'=>array('top','organizationalUnit'),'ou'=>$name));
		foreach ($ldap->read($ini['ldap']['createoudn'],"objectclass=top",array('dn','objectclass')) AS $e ) {
			$this->assertEquals($e->getDn(), $ini['ldap']['createoudn']);
			$this->assertEquals(true,in_array('top',$e->objectclass->getValues()));
		}
		$ldap->modAdd($ini['ldap']['createoudn'],array('description'=>'test'));
		$ldap->modReplace($ini['ldap']['createoudn'],array('description'=>'tset'));
		$ldap->modDel($ini['ldap']['createoudn'],array('description'=>'tset'));
		$ldap->delete($ini['ldap']['createoudn']);
	}
	
	public function testProxyBind() {
		$ini = parse_ini_file("settings.ini",true);
		$ldap = new Ldap($ini['ldap']['host'],$ini['ldap']['port']);
		$ldap->setOption(LDAP_OPT_PROTOCOL_VERSION,3);
		$ldap->bind($ini['ldap']['admindn'],$ini['ldap']['adminpasswd']);
		$ldap->setProxyDN($ini['ldap']['userdn']);
	}

}