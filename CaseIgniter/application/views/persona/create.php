

<div class="container">
<h2> Crear persona </h2>

<form class="row col-sm-4" id="idForm" action="<?= base_url() ?>persona/create_post" method="post">

			
	<div class="form-group">
		<label for="id-nombre">Nombre</label>
		<input id="id-nombre" type="text" name="nombre" class="form-control" autofocus="autofocus">
	</div>

			
	<div class="form-group">
		<label for="id-fechanacimiento">Fechanacimiento</label>
		<input id="id-fechanacimiento" type="date" name="fechanacimiento" class="form-control" >
	</div>

			
	<div class="form-group">
		<label for="id-peso">Peso</label>
		<input id="id-peso" type="number" name="peso" class="form-control" >
	</div>


	<div class="form-group">
		<label for="id-amo">Amo</label>
		<select id="id-amo" name="amo" class="form-control">
			<option value="0"> ----- </option>
			<?php foreach ($body['mascota'] as $mascota ): ?>
				<?php if ( $mascota->amo == null ): ?>
					<option value="<?= $mascota->id ?>"><?= $mascota->nombre ?></option>
				<?php endif; ?> 
			<?php endforeach; ?>
		</select>
	</div>


	<div class="form-group">
		<label for="id-ciudadanos">Ciudadanos</label>
		<select id="id-ciudadanos" name="ciudadanos" class="form-control">
			<option value="0"> ----- </option>
			<?php foreach ($body['pais'] as $pais ): ?>
				
					<option value="<?= $pais->id ?>"><?= $pais->nombre ?></option>
				
			<?php endforeach; ?>
		</select>
	</div>

	<fieldset class="scheduler-border">
		<legend class="scheduler-border">Expertoen</legend>
		<div class="form-check form-check-inline">
			<?php foreach ($body['aficion'] as $aficion ): ?>
				<?php if ( $aficion->expertoen == null ): ?>
					<input class="form-check-input" type="checkbox" id="id-expertoen-<?=$aficion->id?>" name="expertoen[]" value="<?= $aficion->id ?>">
					<label class="form-check-label" for="id-expertoen-<?=$aficion->id?>" ><?= $aficion->nombre ?></label>
				<?php endif; ?> 
			<?php endforeach; ?>
		</div>
	</fieldset>

	<fieldset class="scheduler-border">
		<legend class="scheduler-border">Inutilen</legend>
		<div class="form-check form-check-inline">
			<?php foreach ($body['aficion'] as $aficion ): ?>
				<?php if ( $aficion->inutilen == null ): ?>
					<input class="form-check-input" type="checkbox" id="id-inutilen-<?=$aficion->id?>" name="inutilen[]" value="<?= $aficion->id ?>">
					<label class="form-check-label" for="id-inutilen-<?=$aficion->id?>" ><?= $aficion->nombre ?></label>
				<?php endif; ?> 
			<?php endforeach; ?>
		</div>
	</fieldset>

	<fieldset class="scheduler-border">
		<legend class="scheduler-border">Gusta</legend>
		<div class="form-check form-check-inline">
			<?php foreach ($body['aficion'] as $aficion ): ?>
				
					<input class="form-check-input" type="checkbox" id="id-gusta-<?=$aficion->id?>" name="gusta[]" value="<?= $aficion->id ?>">
					<label class="form-check-label" for="id-gusta-<?=$aficion->id?>" ><?= $aficion->nombre ?></label>
				
			<?php endforeach; ?>
		</div>
	</fieldset>

	<fieldset class="scheduler-border">
		<legend class="scheduler-border">Odia</legend>
		<div class="form-check form-check-inline">
			<?php foreach ($body['aficion'] as $aficion ): ?>
				
					<input class="form-check-input" type="checkbox" id="id-odia-<?=$aficion->id?>" name="odia[]" value="<?= $aficion->id ?>">
					<label class="form-check-label" for="id-odia-<?=$aficion->id?>" ><?= $aficion->nombre ?></label>
				
			<?php endforeach; ?>
		</div>
	</fieldset>


	<input type="submit" class="btn btn-primary" value="Crear">

</form>

<div id="idMessage" class="row col-sm-4">
</div>

</div>	