<?php
/**
* Model code autogenerated by CASE IGNITER
*/
class mascota_model extends CI_Model {
public function create( $nombre ) {
	$bean = R::dispense( 'mascota' );

	// Regular attribute
	$bean -> nombre = $nombre;

	R::store($bean);
}

	public function get_all() {
		return R::findAll('mascota');
	}
	public function get_filtered($filter) {
		return [];
	}

}
?>