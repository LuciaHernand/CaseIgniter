<div class="container">
<form action="<?=base_url()?>pais/create"><input type="submit" class="btn btn-primary" value="Crear pais"></form>
<h1>LISTA de  pais</h1>
<table>
	<tr>
		<th>nombre<th>		<th>nombre</th>
	</tr>

	<?php foreach ($body['pais'] as $pais): ?>
	<?php endforeach; ?>
</table>
</div>