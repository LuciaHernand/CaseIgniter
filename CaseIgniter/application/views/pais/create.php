
<script type="text/javascript" src="<?= base_url() ?>assets/js/serialize.js"></script>

<script type="text/javascript">
var connection;

function create() {
	var createForm = document.getElementById('idForm');
	var serializedData = serialize(createForm);
	
	connection = new XMLHttpRequest();
	connection.open('POST', '<?= base_url() ?>pais/create_post', true);
	connection.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	connection.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	connection.send(serializedData);
	connection.onreadystatechange = function() {
		if (connection.readyState==4 && connection.status==200) {
			actionAJAX();
		}
	}
}

		
function actionAJAX() {
	htmlReceived = connection.responseText;
	document.getElementById("idMessage").innerHTML = htmlReceived;
}	

</script>

<!--------------------------------------------->

<div class="container">
<h2> Crear pais </h2>

<form class="col-sm-4" id="idForm">

	<div class="form-group">
		<label for="id-nombre">Nombre</label>
		<input id="id-nombre" type="text" name="nombre" class="form-control">
	</div>

	<fieldset>
		<legend>Personas</legend>
		<div class="form-group">
			<?php foreach ($body['persona'] as $persona ): ?>
				<label for="id-pais_nacimiento" class="checkbox-inline">Pais</label>
				<input type="checkbox" id="id-pais_nacimiento" name="pais_nacimiento[]" class="form-control" value="persona->id">

			<?php endforeach; ?>

		</div>
	</fieldset>


	<input type="button" class="btn btn-primary" onclick="create()" value="Crear">

</form>

<div id="idMessage" class="col-sm-4">
</div>

</div>	