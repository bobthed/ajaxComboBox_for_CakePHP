<!doctype html>
<html>
	<head>
		<meta charset="UTF-8">
		<?php
			echo $this->Html->css('jquery.ajaxComboBox');
			echo $this->Html->script('http://code.jquery.com/jquery.min.js');
			echo $this->Html->script('jquery.ajaxComboBox.6.1');
		?>
		<script type="text/javascript">
			var webroot = '<?php echo $this->webroot ?>';
			var init_record = '<?php echo $init_record ?>';

			jQuery(document).ready(function($){
				//Combo-Box
				$('#acbox_test').ajaxComboBox(
					webroot + 'nations/ajax_search',
					{
						lang        : 'en',
						db_table    : 'nation',
						field       : 'name',
						primary_key : 'id',
						init_record : init_record,
						select_only : true,
						button_img  : webroot + 'img/jquery.ajaxComboBox.button.png'
					}
				);
				//Text Area
				$('#acbox_test2').ajaxComboBox(
					webroot + 'nations/ajax_search',
					{
						lang        : 'en',
						plugin_type : 'textarea',
						db_table    : 'tag',
						shorten_url : '#acbox_test2_shorten',
						shorten_src : webroot + 'nations/shorten_url',
						shorten_min : 20,
						tags        : [
							{
								pattern  : ['[', ']'],
								space    : [false, false]
							},
							{
								pattern  : ['#', '']

							},
							{
								pattern  : ['@', '']
							}
						],
					}
				);
			});
		</script>
	</head>
	<body>
		<?php echo $content_for_layout ?>
	</body>
</html>
