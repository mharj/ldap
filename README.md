# ldap
PHP Ldap Class

```php
$ldap = new Ldap('127.0.0.1',389);
try {
	$ldap->bind();
	$query = new LdapQuery('dc=example,dc=com','(uid=b*)',array('uid'));	
	$entries = $ldap->query( $query );	
	foreach( $entries AS $entry ) {
		print_r($entry);
	}
	foreach( $entries->sort('uid') AS $entry ) {
		print_r($entry);
	}
	$ldap->close();
} catch( LdapException $ex ) {
	echo $ex->getMessage();
}
$ldap->close();
```
