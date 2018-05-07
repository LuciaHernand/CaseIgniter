<?php
/**
* Model code autogenerated by CASE IGNITER
*/
class gatito_model extends CI_Model {


	/**
	* create MODEL action autogenerated by CASE IGNITER
	*/
	public function create( $nombre, $expuesto, $loginname, $password, $roles) {

	R::begin();
	try {

	$bean = R::dispense( 'gatito' );
	$id_bean = R::store( $bean );
	
	// Regular attribute
	$bean -> nombre = $nombre;

	// Regular attribute
	$bean -> expuesto = $expuesto;

	// Regular attribute
	$bean -> loginname = $loginname;

	// Regular attribute
	$bean -> password = $password;
					
	// "many to many" attribute
	foreach ($roles as $id) {
		$another_bean = R::load('rol', $id);
		$m2m = R::dispense('roles');
		R::store($bean);
		$m2m -> gatito = $bean;
		$m2m -> rol = $another_bean;
		R::store($m2m);
	}
				
				
	R::store($bean);
	R::commit();
	return $bean->id;

	}
	catch (Exception $e) {
		R::rollback();
		throw $e;
	}

	}

	/**
	* update MODEL action autogenerated by CASE IGNITER
	*/
	public function update( $id, $nombre, $expuesto, $loginname, $password, $roles, $is_admin) {

	R::begin();

	try {
	$bean = R::load( 'gatito', $id );
					
	// Regular attribute
	$bean -> nombre = $nombre;
						
	// Regular attribute
	$bean -> expuesto = $expuesto;
						
	// Regular attribute
	$bean -> loginname = $loginname;
						
	// Regular attribute
	$bean -> password = $password;
					
	// "many to many" attribute (M2M)
	
	if ($roles != [] && $is_admin ) {

	foreach ($bean->ownRolesList as $roles_bean ) {
		$key = array_search( $roles_bean->rol->id, $roles );
		
		if ($key !== false) { // M2M we keep only the keys to add
			unset($roles[$key]);
		}
		else { // M2M Element to be deleted
			R::store($bean);
			R::trash($roles_bean);
		}
	}

	// M2M Elements to be added
	foreach ($roles as $idf) {
		$another_bean = R::load('rol', $idf);
		$m2m = R::dispense('roles');
		$m2m -> gatito = $bean;
		$m2m -> rol = $another_bean;
		R::store($m2m);
	}
	}

	
	R::store($bean);
	R::commit();
	}
	catch (Exception $e) {
		R::rollback();
		throw $e;
	}

	}

	/**
	* get_all MODEL action autogenerated by CASE IGNITER
	*/
	public function get_all() {
		return R::findAll('gatito');
	}

	/**
	* get_filtered MODEL action autogenerated by CASE IGNITER
	*/
	public function get_filtered($filter) {

		$where_clause = [ ];

		$where_clause[] = 'nombre LIKE ?';
		$where_clause[] = 'expuesto LIKE ?';
		$where_clause[] = 'oculto LIKE ?';
		$where_clause[] = 'loginname LIKE ?';
		$where_clause[] = '(SELECT count(*) FROM rol WHERE nombre LIKE ? AND rol.id IN (SELECT rol_id FROM roles WHERE gatito_id = gatito.id)) > 0';
		$f = "%$filter%";
		
		return R::findAll('gatito', implode(' OR ', $where_clause) , [ $f,$f,$f,$f,$f ] );
		
	}

	/**
	* delete MODEL action autogenerated by CASEIGNITER
	*/
	public function delete( $id ) {
		$bean = R::load('gatito', $id );

		R::trash( $bean );
	}	
	/**
	* get_by_id MODEL action autogenerated by CASEIGNITER
	*/
	public function get_by_id( $id ) {
		return R::load('gatito', $id ) ;
	}
		/**
	 * create MODEL action autogenerated by CASE IGNITER
	 */
	public function get_by_loginname( $loginname ) {
		return R::findOne( 'gatito', ' loginname = ? ', [ $loginname ] );
	}
				/**
	* change_password MODEL action autogenerated by CASEIGNITER
	*/
	public function change_password($id,$old_password,$new_password) {

		$bean = R::load('gatito',$id);
		if ( ! password_verify ( $old_password, $bean->password ) ) {
			throw new Exception("ERROR: Contraseña incorrecta");
		}
		else {
			$bean->password = password_hash ( $new_password, PASSWORD_DEFAULT );
			R::store($bean);
		}
		
	}
}
?>