<?php
/**
* Controller code for persona autogenerated by CASE IGNITER
*/
class persona extends CI_Controller {

	/**
	* Controller action CREATE for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function create() {

		$data['body']['filter'] = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '' ;

		$this->load->model('mascota_model');
		$data['body']['mascota'] = $this->mascota_model->get_all();

		$this->load->model('pais_model');
		$data['body']['pais'] = $this->pais_model->get_all();

		$this->load->model('aficion_model');
		$data['body']['aficion'] = $this->aficion_model->get_all();

		$this->load->model('rol_model');
		$data['body']['rol'] = $this->rol_model->get_all();
		enmarcar($this, 'persona/create', $data);
	}

	
	
	/**
	* Controller action CREATE POST for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function create_post() {
		
		$this->load->model('persona_model');

		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );
		$fechanacimiento = ( isset( $_POST['fechanacimiento']) ? $_POST['fechanacimiento'] : null );
		$peso = ( isset( $_POST['peso']) ? $_POST['peso'] : null );
		$foto = ( isset( $_FILES['foto']) ? $_FILES['foto'] : null );
		$amo = ( isset( $_POST['amo']) ? $_POST['amo'] : [] );
		$paisnacimiento = ( isset( $_POST['paisnacimiento']) ? $_POST['paisnacimiento'] : [] );
		$expertoen = ( isset( $_POST['expertoen']) ? $_POST['expertoen'] : [] );
		$inutilen = ( isset( $_POST['inutilen']) ? $_POST['inutilen'] : [] );
		$gusta = ( isset( $_POST['gusta']) ? $_POST['gusta'] : [] );
		$odia = ( isset( $_POST['odia']) ? $_POST['odia'] : [] );
		$loginname = ( isset( $_POST['loginname']) ? $_POST['loginname'] : null );
		$password = ( isset( $_POST['password']) ? $_POST['password'] : null );
		$roles = ( isset( $_POST['roles']) ? $_POST['roles'] : [] );

		try {
			$id = $this->persona_model->create( $nombre, $fechanacimiento, $peso, $foto, $amo, $paisnacimiento, $expertoen, $inutilen, $gusta, $odia, $loginname, $password, $roles );
			$this->list_id($id);
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la persona $nombre";
			$this->load->view('persona/create_message',$data);
		}	
	
	}
				
				
	
	/**
	* Controller action LIST for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function list() {
		$this->load->model('persona_model');
		$filter = isset($_REQUEST['filter'])?$_REQUEST['filter']:'';
		$data['body']['persona'] = ($filter == '' ? $this->persona_model->get_all() : $this->persona_model->get_filtered( $filter ) );
		$data['body']['filter'] = $filter ;
		enmarcar($this, 'persona/list', $data);
	}

	/**
	* Controller private function LIST_ID for controller persona
	* autogenerated by CASE IGNITER
	*/
	private function list_id($id) {
		$this->load->model('persona_model');
		$data['body']['persona'] = [ $this->persona_model->get_by_id($id) ];
		enmarcar($this, 'persona/list', $data);
	}


	
	/**
	* Controller action DELETE for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function delete() {
		$this -> load -> model ('persona_model');
		try {
			$id = $_POST['id'];
			$filter = isset ($_REQUEST['filter'] ) ? $_REQUEST['filter'] : '';

			$this -> persona_model -> delete( $id );
			redirect(base_url().'persona/list?filter='.$filter);
		}
		catch (Exception $e ) {
			enmarcar($this, 'persona/deleteERROR');
		}
	}	
	
	
	/**
	* Controller action UPDATE for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function update() {

		$data['body']['filter'] = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '' ;
	
	
		$this->load->model('mascota_model');
		$data['body']['mascota'] = $this->mascota_model->get_all();	
	
		$this->load->model('pais_model');
		$data['body']['pais'] = $this->pais_model->get_all();	
	
		$this->load->model('aficion_model');
		$data['body']['aficion'] = $this->aficion_model->get_all();	
	
		$this->load->model('rol_model');
		$data['body']['rol'] = $this->rol_model->get_all();

		$this -> load -> model ('persona_model');
		$id = $_POST['id'];
		$data['body']['persona'] = $this -> persona_model -> get_by_id($id);
		
		enmarcar($this, 'persona/update', $data);
	}	
	
	/**
	* Controller action UPDATE POST for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function update_post() {
	
		$this->load->model('persona_model');
			
		$id = ( isset( $_POST['id']) ? $_POST['id'] : null );
		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );
		$fechanacimiento = ( isset( $_POST['fechanacimiento']) ? $_POST['fechanacimiento'] : null );
		$peso = ( isset( $_POST['peso']) ? $_POST['peso'] : null );
		$foto = ( isset( $_FILES['foto']) ? $_FILES['foto'] : null );
		$amo = ( isset( $_POST['amo']) ? $_POST['amo'] : [] );
		$paisnacimiento = ( isset( $_POST['paisnacimiento']) ? $_POST['paisnacimiento'] : [] );
		$expertoen = ( isset( $_POST['expertoen']) ? $_POST['expertoen'] : [] );
		$inutilen = ( isset( $_POST['inutilen']) ? $_POST['inutilen'] : [] );
		$gusta = ( isset( $_POST['gusta']) ? $_POST['gusta'] : [] );
		$odia = ( isset( $_POST['odia']) ? $_POST['odia'] : [] );
		$loginname = ( isset( $_POST['loginname']) ? $_POST['loginname'] : null );
		$password = ( isset( $_POST['password']) ? $_POST['password'] : null );
		$roles = ( isset( $_POST['roles']) ? $_POST['roles'] : [] );

		try {
			$this->persona_model->update( $id, $nombre, $fechanacimiento, $peso, $foto, $amo, $paisnacimiento, $expertoen, $inutilen, $gusta, $odia, $loginname, $password, $roles );

			$filter = isset($_POST['filter']) ? $_POST['filter'] : '' ;
			redirect( base_url() . 'persona/list?filter='.$filter );
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la persona $nombre";
			$this->load->view('persona/create_message',$data);
		}	
	
	}
			
			}
?>