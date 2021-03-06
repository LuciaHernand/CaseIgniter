<?php


	function get_ids($beans) {
		$sol = [];
		foreach ($beans as $bean) {
			$sol[] = $bean -> id;
		}
		return $sol;
	}

	function selected($bean_selected, $id_to_be_tested) {
		return $bean_selected != null && $bean_selected->id == $id_to_be_tested ? 'selected="selected"' : '';
	}

	function checked($list, $id_to_be_tested) {
		return in_array($id_to_be_tested, get_ids($list) ) ? 'checked="checked"' : '';
	}
?>	
	
<div class="container">
<h2> Editar rol </h2>

<form class="form" role="form" id="idForm" enctype="multipart/form-data" action="<?= base_url() ?>rol/updatePost" method="post">
	
	<input type="hidden" name="filter" value="<?=$body['filter']?>" />
	
		
	<input type="hidden" name="id" value="<?= $body['rol']->id ?>">

	<div class="row form-inline form-group" >
		<label for="id-nombre" class="col-2 justify-content-end">Nombre</label>
		<input id="id-nombre" type="text" name="nombre" value="<?=  $body['rol']->nombre ?>" class="col-6 form-control" >
		
	</div>
				
				
	<div class="row form-inline form-group" >
		<label for="id-descripcion" class="col-2 justify-content-end">Descripcion</label>
		<input id="id-descripcion" type="text" name="descripcion" value="<?=  $body['rol']->descripcion ?>" class="col-6 form-control" >
		
	</div>
				
								
	<?php if ($body['is_admin']): ?>
	<div class="row form-inline form-group">
		<label class="col-2 justify-content-end">Roles</label>
		<div class="col-6 form-check form-check-inline justify-content-start">

			<?php foreach ($body['usuario'] as $usuario ): ?>
				
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" id="id-roles-<?=$usuario->id ?>" name="roles[]" value="<?= $usuario->id ?>" <?= checked($body['rol']->aggr('ownRolesList','usuario'), $usuario->id ) ?>>
					<label class="form-check-label" for="id-roles-<?=$usuario->id?>" ><?= $usuario->nombre ?></label>
				</div>
				
			<?php endforeach; ?>
						
		</div>
	</div>
	<?php endif; ?>

				
	<?php if ($body['is_admin']): ?>
	<div class="row form-inline form-group">
		<label class="col-2 justify-content-end">Rolrequest</label>
		<div class="col-6 form-check form-check-inline justify-content-start">

			<?php foreach ($body['usuario'] as $usuario ): ?>
				
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" id="id-rolrequest-<?=$usuario->id ?>" name="rolrequest[]" value="<?= $usuario->id ?>" <?= checked($body['rol']->aggr('ownRolrequestList','usuario'), $usuario->id ) ?>>
					<label class="form-check-label" for="id-rolrequest-<?=$usuario->id?>" ><?= $usuario->nombre ?></label>
				</div>
				
			<?php endforeach; ?>
						
		</div>
	</div>
	<?php endif; ?>

<div class="row offset-2 col-6">
	<input type="submit" class="btn btn-primary" value="Actualizar">
</form>


<form action="<?=base_url()?>rol/list" method="post">
	<input type="hidden" name="filter" value="<?=$body['filter']?>" />
	<input type="submit" class="offset-1 btn btn-primary" value="Cancelar">
</form>

</div>

			