<?php
require_once 'xapi.php';
require_once 'set_opt.php';

$session = new XAPI($user_login, $user_hash, $protocol, $subdomain);

$session->authorization();
$leads = $contacts = $companies = [];
if (isset($argv[1]) && is_int((int)$argv[1]) && $argv[1] > 0) {
    $count = $argv[1];
} else {
    echo "Ошибка ввода данных!!";
}
for ($i = 0; $i < $count; $i++) {
	$leads = $contacts = $companies = [];
	$contacts['add'][] = [
        'name' => 'contact_'.$i
	];	
	$contact_id = $session->set_contacts_list($contacts);
    echo sprintf('Was added contact_%d %s', $i, PHP_EOL);
	$companies['add'][] = [
		'name' => 'company_'.$i,
		'contacts_id' => [
			(string)$contact_id
		] 
	];
	$company_id = $session->set_companies_list($companies);
	echo sprintf('Was added company_%d %s', $i, PHP_EOL);
	$leads['add'][] = [
		'name' => 'lead_'.$i,
		'company_id' => $company_id,
		'contacts_id' => [
			(string)$contact_id
		]
	];
	$lead_id = $session->set_leads_list($leads);
	echo sprintf('Was added lead_%d %s %s %s', $i, PHP_EOL, PHP_EOL, PHP_EOL);
}
