<?php

class XAPI
{
	private $_user;
	private $_protocol;
	private $_subdomain;
	const URN = [
		'auth' => 'private/api/auth.php',
		'leads' => 'api/v2/leads',
		'contacts' => 'api/v2/contacts',
		'companies' => 'api/v2/companies',
		'add_fields' => 'api/v2/fields',
		'get_fields' => 'api/v2/account',
		'tasks' => 'api/v2/tasks'
	];
	public function __construct($userLogin, $userHash, $protocol, $subdomain){
		$this->_user = array(
			'USER_LOGIN'=> $userLogin,
			'USER_HASH' => $userHash
		);
		$this->_protocol = $protocol;
		$this->_subdomain = $subdomain;
	}
	private function generate_link($entities_type, $params = ''){
		try {
			if (isset(self::URN[$entities_type])) {
				$link = sprintf("%s%s%s%s", 
					$this->_protocol, 
					$this->_subdomain, 
					self::URN[$entities_type], 
					$params
				);
			} else {
				throw new Exception("Неправильные переданные параметры");
			}
		}
		catch (Exception $e) {
			echo 'Выброшено исключение: ', $e->getMessage();
		}
		return $link;
	}
	
	private function cURL_func($link, $datas = NULL){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
		curl_setopt($curl, CURLOPT_URL, $link);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt'); 
		curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		if ($datas != NULL) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($datas));
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		}
		$out = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		$code = (int)$code;
		$errors = [
		  301=>'Moved permanently',
		  400=>'Bad request',
		  401=>'Unauthorized',
		  403=>'Forbidden',
		  404=>'Not found',
		  500=>'Internal server error',
		  502=>'Bad gateway',
		  503=>'Service unavailable'
		];
		try
		{
		 if($code != 200 && $code != 204) {
		    throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
		  }
		}
		catch(Exception $E)
		{
		  die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
		}
		$response = json_decode($out, TRUE);
		return $response;
	}
	public function authorization(){
		$params = '?type=json';
		$response = $this->cURL_func($this->generate_link('auth', $params), $this->_user);
		return $response['response'];
	}
	public function get_leads_list(){
		$leads_list_get = $sum_leads_list = [];
		$limit_offset = 0;
		$limit_rows = 50;
		while (TRUE) {
			$params = '?limit_rows='.$limit_rows.'&limit_offset='.$limit_offset;
			$response = $this->cURL_func($this->generate_link('leads', $params), NULL);
			$response = $response['_embedded']['items'];
			if ($response == NULL) {
				break;
			}
			foreach ($response as $value) {
				$sum_leads_list[] = $value;
			}
			$limit_offset += $limit_rows;
		}
		if (!is_array($sum_leads_list)) {
			$sum_leads_list = NULL;
		}	
		return $sum_leads_list;
	}
	public function set_leads_list($leads_list){
		$response = $this->cURL_func($this->generate_link('leads'), $leads_list);
		$response = $response['_embedded']['items'];
		return $response[0]['id'];
	}
	public function update_leads_list($leads_list){
		echo sprintf("Выполняется отправка изменений на сервер..%s", PHP_EOL);
		$response = $this->cURL_func($this->generate_link('leads'), $leads_list);
		echo sprintf("Данные успешно обновлены.%s", PHP_EOL);
		return $response;
	}
	public function set_contacts_list($contacts_list){
		$response = $this->cURL_func($this->generate_link('contacts'), $contacts_list);
		$response = $response['_embedded']['items'];
		return $response[0]['id'];
	}
	public function set_companies_list($companies_list){
		$response = $this->cURL_func($this->generate_link('companies'), $companies_list);
		$response = $response['_embedded']['items'];
		return $response[0]['id'];
	}
	public function add_custom_multiple_field($custom_field){
		$response = $this->cURL_func($this->generate_link('add_fields'), $custom_field);
		if (!is_array($response['_embedded']['items']) && empty($response['_embedded']['items'])) {
			$response = NULL;
		}
		return $response;
	}
	public function get_custom_multiple_fields(){
		$params = '?with=custom_fields';
		$response = $this->cURL_func($this->generate_link('get_fields', $params), NULL);
		if(!isset($response['_embedded']['custom_fields']['leads']) || empty($response['_embedded']['custom_fields']['leads'])) {
			$response = NULL;
		} else {
			$response = $response['_embedded']['custom_fields']['leads'];
		}
		return $response;
		
	}	
	public function extract_leads_id(){
		$sum_leads_list = $this->get_leads_list();
		if (is_array($sum_leads_list) && !empty($sum_leads_list)) {
			echo sprintf("Выполняется преобразование полученных сделок..%s", PHP_EOL);
			$leads_id = [];
			foreach ($sum_leads_list as $key => $value) {
				$leads_id[] = $value['id'];
			}
		} else {
			echo "Ошибка получения списка сделок";
			$leads_id = NULL;
		}
		return $leads_id;
		
	}	
	public function extract_fields_id(){
		$fields_enums_id = [];
		$fields_list = $this->get_custom_multiple_fields();
		if (is_array($fields_list)) {
			$fields_id = array_keys($fields_list);
			if (!empty($fields_id)) {
				foreach ($fields_id as $key => $value) {
					if(isset($fields_list[$value]['enums'])) {
						$fields_enums_id[] = [
							'field_id' => $value,
							'enums_id' => array_keys($fields_list[$value]['enums'])
						];	
					}
				}	
			} else {
				$fields_enums_id = NULL;
			}
		} else {
			$fields_enums_id = NULL;
		}
		return $fields_enums_id;
	}
}
