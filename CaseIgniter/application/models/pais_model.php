<?php
/**
* Model code autogenerated by CASE IGNITER
*/
class pais_model extends CI_Model {
public function create( $nombre ) {
	$bean = R::dispense( 'pais' );

	// Regular attribute
	$bean -> nombre = $nombre;

	R::store($bean);
}

	public function get_all() {
		return R::findAll('pais');
	}
	public function get_filtered($filter) {
		return [];
	}

	/**
	* model delete action autogenerated by CASEIGNITER
	*/
	public function delete( $id ) {
		$bean = R::load('pais', $id );
		R::trash( $bean );
	}

}
?>