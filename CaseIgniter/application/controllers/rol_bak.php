<?php
/**
* Controller code for rol autogenerated by CASE IGNITER
*/
class rol extends CI_Controller {	
	
	/**
	* Controller action CREATE for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function create() {
	
		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 
		
		$data['body']['filter'] = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '' ;
		$this->load->model('gatito_model');
		$data['body']['gatito'] = $this->gatito_model->get_all();
	
		frame($this, 'rol/create', $data);
	}
				
					
	
	/**
	* Controller action CREATE POST for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function create_post() {
		
		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 

		$this->load->model('rol_model');

		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );
		$descripcion = ( isset( $_POST['descripcion']) ? $_POST['descripcion'] : null );
		$roles = ( isset( $_POST['roles']) ? $_POST['roles'] : [] );

		try {
			$id = $this->rol_model->create( $nombre, $descripcion, $roles );
			$this->list_id($id);
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la rol $nombre";
			frame($this,'rol/create_message',$data);
		}	
	
	}
				
				
	
	/**
	* Controller action LIST for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function list() {

		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 


		$this->load->model('rol_model');
		$filter = isset($_REQUEST['filter'])?$_REQUEST['filter']:'';
		$data['body']['rol'] = ($filter == '' ? $this->rol_model->get_all() : $this->rol_model->get_filtered( $filter ) );
		$data['body']['filter'] = $filter ;
		frame($this, 'rol/list', $data);
	}

	/**
	* Controller private function LIST_ID for controller rol
	* autogenerated by CASE IGNITER
	*/
	private function list_id($id) {
		$this->load->model('rol_model');
		$data['body']['rol'] = [ $this->rol_model->get_by_id($id) ];
		frame($this, 'rol/list', $data);
	}


	
	/**
	* Controller action DELETE for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function delete() {

		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 


		$this -> load -> model ('rol_model');
		try {
			$id = $_POST['id'];
			$filter = isset ($_REQUEST['filter'] ) ? $_REQUEST['filter'] : '';

			$this -> rol_model -> delete( $id );
			redirect(base_url().'rol/list?filter='.$filter);
		}
		catch (Exception $e ) {
			frame($this, 'rol/deleteERROR');
		}
	}	
	
	
	/**
	* Controller action UPDATE for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function update() {

		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 

		
		if (session_status () == PHP_SESSION_NONE) { session_start (); }
		$is_admin = ( isset($_SESSION['rol']) && $_SESSION['rol']->nombre == 'admin' );
		$data['body']['is_admin'] = $is_admin;
		$data['body']['filter'] = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '' ;
	
	
		$this->load->model('gatito_model');
		$data['body']['gatito'] = $this->gatito_model->get_all();

		$this -> load -> model ('rol_model');
		$id = $_POST['id'];
		$data['body']['rol'] = $this -> rol_model -> get_by_id($id);
		
		frame($this, 'rol/update', $data);
	}	
	
	/**
	* Controller action UPDATE POST for controller rol
	* autogenerated by CASE IGNITER
	*/
	public function updatePost() {
	
		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$rol_ok = false;
		$login_rol = (isset($_SESSION['rol']) ? $_SESSION['rol']->nombre : null );
		if ($login_rol == 'admin') {$rol_ok = true;}

		if ( !$rol_ok ) {
			show_404();
		} 


		$this->load->model('rol_model');
			
		$id = ( isset( $_POST['id']) ? $_POST['id'] : null );
		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );
		$descripcion = ( isset( $_POST['descripcion']) ? $_POST['descripcion'] : null );
		$roles = ( isset( $_POST['roles']) ? $_POST['roles'] : [] );
		try {
			$this->rol_model->update( $id, $nombre, $descripcion, $roles );

			$filter = isset($_POST['filter']) ? $_POST['filter'] : '' ;
			redirect( base_url() . 'rol/list?filter='.$filter );
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la rol $nombre";
			frame($this,'rol/create_message',$data);
		}	
	
	}
			
			}
?>