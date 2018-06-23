<?php
require_once 'amoapi.php';
require_once 'set_opt.php';

$session = new XAPI($user_login, $user_hash, $protocol, $subdomain);

$session->authorization();
echo sprintf("Выполняется формирование мультисписка..%s", PHP_EOL);
$custom_field = [];
$custom_field['add'][] = [
	'name' => "Мой мультисписок_new",
	'field_type'=> 5,
	'element_type' => 2,
	'origin' => "mymultiple228",
	'is_editable' => TRUE,
	'enums' => array(
         "значение1",
         "значение2",
         "значение3",
         "значение4",
         "значение5",
         "infinite"
      )
];
echo sprintf("Выполняется отправка изменений на сервер..%s", PHP_EOL);
$session->add_custom_multiple_field($custom_field);
echo sprintf("Мультисписок успешно добавлен.%s", PHP_EOL);
