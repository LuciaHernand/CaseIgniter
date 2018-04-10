<?php
/**
* Controller code for pais autogenerated by CASE IGNITER
*/
class pais extends CI_Controller {

	/**
	* Controller action CREATE for controller pais
	* autogenerated by CASE IGNITER
	*/
	public function create() {

		$data = [];

		enmarcar($this, 'pais/create', $data);
	}

	
	
	/**
	* Controller action CREATE POST for controller pais
	* autogenerated by CASE IGNITER
	*/
	public function create_post() {
		
		$this->load->model('pais_model');

		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );

		try {
			$this->pais_model->create( $nombre );
			$data['status'] = 'ok';
			$data['message'] = "Pais $nombre creado/a correctamente";
			$this->load->view('pais/create_message',$data);
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la pais $nombre";
			$this->load->view('pais/create_message',$data);
		}	
	
	}
				
				
	public function list() {
		$this->load->model('pais_model');
		$filter = isset($_POST['filter'])?$_POST['filter']:'';
		$data['body']['pais'] = ($filter == '' ? $this->pais_model->get_all() : $this->pais_model->get_filtered( $filter ) );
		enmarcar($this, 'pais/list', $data);
	}

	
	/**
	* Controller action DELETE for controller pais
	* autogenerated by CASE IGNITER
	*/
	public function delete() {
		$this -> load -> model ('pais_model');
		try {
			$id = $_POST['id'];
			$this -> pais_model -> delete( $id );
			redirect(base_url().'pais/list');
		}
		catch (Exception $e ) {
			enmarcar($this, 'pais/deleteERROR');
		}
	}	
	
	
	/**
	* Controller action UPDATE for controller pais
	* autogenerated by CASE IGNITER
	*/
	public function update() {
		$this -> load -> model ('pais_model');
		$id = $_POST['id'];
		$data['body']['pais'] = $this -> pais_model -> get_by_id($id);
		
		enmarcar($this, 'pais/update', $data);
	}	
	
	/**
	* Controller action UPDATE POST for controller pais
	* autogenerated by CASE IGNITER
	*/
	public function update_post() {
	
		$this->load->model('pais_model');
			
		$id = ( isset( $_POST['id']) ? $_POST['id'] : null );
		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );

		try {
			$this->pais_model->update( $id, $nombre );


			redirect( base_url() . 'pais/list' );
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = "Error al crear el/la pais $nombre";
			$this->load->view('pais/create_message',$data);
		}	
	
	}
			
			}
?>