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

		$this->load->model('mascota_model');
		$data['body']['mascota'] = $this->mascota_model->get_all();

		$this->load->model('pais_model');
		$data['body']['pais'] = $this->pais_model->get_all();

		$this->load->model('aficion_model');
		$data['body']['aficion'] = $this->aficion_model->get_all();

		enmarcar($this, 'persona/create', $data);
	}

	
	
	/**
	* Controller action CREATE POST for controller persona
	* autogenerated by CASE IGNITER
	*/
	public function create_post() {
		
		$this->load->model('persona_model');

		$nombre = ( isset( $_POST['nombre']) ? $_POST['nombre'] : null );
		$fecha_nac = ( isset( $_POST['fecha_nac']) ? $_POST['fecha_nac'] : null );
		$peso = ( isset( $_POST['peso']) ? $_POST['peso'] : null );
		$amo = ( isset( $_POST['amo']) ? $_POST['amo'] : null );
		$pais_nacimiento = ( isset( $_POST['pais_nacimiento']) ? $_POST['pais_nacimiento'] : null );
		$tiene_pa = ( isset( $_POST['tiene_pa']) ? $_POST['tiene_pa'] : [] );

		try {
			$this->persona_model->create( $nombre, $fecha_nac, $peso, $amo, $pais_nacimiento, $tiene_pa );
			$data['status'] = 'ok';
			$data['message'] = "Persona $nombre creado/a correctamente";
			$this->load->view('persona/create_message',$data);
		}
		catch (Exception $e) {
			$data['status'] = 'error';
			$data['message'] = 'Error al crear el/la persona $nombre';
			$this->load->view('persona/create_message',$data);
		}	
	
	}
				
					public function list() {
		$this->load->model('persona_model');
		$filter = isset($_POST['filter'])?$_POST['filter']:'';
		$data['body']['persona'] = ($filter == '' ? $this->persona_model->get_all() : $this->persona_model->get_filtered( $filter ) );
		enmarcar($this, 'persona/list', $data);
	}}
?>