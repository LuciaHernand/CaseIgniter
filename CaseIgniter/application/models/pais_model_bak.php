<?php
/**
* Model code autogenerated by CASE IGNITER
*/
class pais_model extends CI_Model {


	/**
	* create MODEL action autogenerated by CASE IGNITER
	*/
	public function create( $nombre ) {

	$bean = R::dispense( 'pais' );


	// Regular attribute
	$bean -> setMeta("buildcommand.unique" , array(array('nombre')) );
	$bean -> nombre = $nombre;

	R::store($bean);
}


	/**
	* update MODEL action autogenerated by CASE IGNITER
	*/
	public function update( $id, $nombre ) {

	$bean = R::load( 'pais', $id );


	// Regular attribute
	$bean -> nombre = $nombre;

	R::store($bean);
}


	/**
	* get_all MODEL action autogenerated by CASE IGNITER
	*/
	public function get_all() {
		return R::findAll('pais');
	}

	/**
	* get_filtered MODEL action autogenerated by CASE IGNITER
	*/
	public function get_filtered($filter) {
		return [];
	}

	/**
	* delete MODEL action autogenerated by CASEIGNITER
	*/
	public function delete( $id ) {
		$bean = R::load('pais', $id );
		R::trash( $bean );
	}
	
	/**
	* get_by_id MODEL action autogenerated by CASEIGNITER
	*/
	public function get_by_id( $id ) {
		return R::load('pais', $id );
	}
	
}
?>