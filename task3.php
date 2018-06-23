<?php
require_once 'amoapi.php';
require_once 'set_opt.php';

$session = new XAPI($user_login, $user_hash, $protocol, $subdomain);
$session->authorization();
$fields_enums_id = $session->extract_fields_id();
if ($fields_enums_id != NULL) {
	$rnd1 = array_rand($fields_enums_id);
	$rnd2 = array_rand($fields_enums_id[$rnd1]['enums_id']);
	$field_id = $fields_enums_id[$rnd1]['field_id'];
	$enum_id = $fields_enums_id[$rnd1]['enums_id'][$rnd2];
	$leads_id = $session->extract_leads_id();
	foreach ($leads_id as $key => $value) {
		$leads_list['update'][] = [
			'id' => $value,
			'updated_at' => time(),
			'name' => 'Новое1 имя_'.$key,
			'custom_fields' => [
				[
					'id' => $field_id,
					'values' => [
						'value' => $enum_id
					]
				]
			]
		];
	}
	echo sprintf("Выполняется добавление новых изменений в сделки..%s", PHP_EOL);
	$session->update_leads_list($leads_list);
} else {
	echo "Ошибка чтения данных!";
}
