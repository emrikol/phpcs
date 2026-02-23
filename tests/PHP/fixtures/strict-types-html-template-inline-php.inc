<div id="wrapper">
	<?php
	foreach ( $projects as $project ) :
		?>
		<div class="box">
			<div class="title"><?php echo esc_html( $project['name'] ); ?></div>
		</div>
	<?php endforeach; ?>
</div>
