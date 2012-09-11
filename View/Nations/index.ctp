<h1>ajaxComboBox6.1 + CakePHP 2.2.2</h1>
<?php
	echo $this->Form->create('index', array('url'=>'index'));
	echo $this->Form->label('tags', 'Combo-Box (initial value is "Japan")', array('for'=>'acbox_test'));
	echo $this->Form->text('search', array('id' => 'acbox_test'));
	echo $this->Form->label('tags', 'Text Area', array('for'=>'acbox_test2'));
	echo $this->Form->textarea('tags', array(
		'id' => 'acbox_test2',
		'rows' =>10,
		'cols' =>60,
		'value'=> <<< EOF
[math][his] #phi #gy
http://ja.wikipedia.org/wiki/あにゃまる探偵キルミンずぅ good.
(http://www.google.com/) good.
ftp://too.short bad.("shorten_min" option)
Do not touch URLhttp://www.google.com/ bad.
www.google.com bad. (needs protocol)
@lo @lin
EOF
	));
	echo $this->Form->button('Shorten', array(
		'type'=>'button',
		'id'=>'acbox_test2_shorten'
	));
	echo $this->Form->end(array('label' => 'submit'));
	if (isset($result)) {
		echo '<h4>Results</h4>';
		pr($result);
	}
?>
