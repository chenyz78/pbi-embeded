<?php
error_reporting(0);

class ReportUser 
{
	public $userName;
	public $password;

	function __construct($userName, $password) 
	{
		$this->userName = $userName;
		$this->password = $password;
	}
};

class Report 
{
	private $powerBi;
	public $id;
	public $name;
	public $data;

	function __construct($powerBi, $data) 
	{
		$this->powerBi = $powerBi;
		$this->id = $data['id'];
		$this->name = $data['name'];
		$this->data = $data;
	}

	function getEmbedUrl() 
	{
		return $this->data['embedUrl'];
	}
};

class PowerBi 
{
	private $reportUser;
	private $clientId;
	private $baseUrl;
	private $groupId;
	private $reports = array();
	private $embedToken;
	private $dashboard;

	function __construct($reportUser, $clientId, $baseUrl, $groupId) 
	{
		$this->reportUser= $reportUser;
		$this->clientId = $clientId;
		$this->baseUrl = $baseUrl;
		$this->groupId = $groupId;
	}

	function getReport($report_id) 
	{
		$this->getReports();
		if (!empty($this->reports)) 
		{
			$report = array_filter($this->reports, function($value) use ($report_id) {
				return $value->id === $report_id;
				});
			return reset($report);
		}
		return FALSE;
	}

	function getReports($refresh = FALSE) {
		if (empty($this->reports) || $refresh) {
			$this->getRest("reports");
		}

		return $this->reports;
	}
	function getDashboard($refresh = FALSE) {
		if (empty($this->dashboard) || $refresh) {
			$this->getRest("dashboards");
		}

		return $this->dashboard;
	}

	private function getEmbedToken() {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://login.windows.net/common/oauth2/token",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => array(
				grant_type => "password",
				scope => "openid",
				resource => "https://analysis.windows.net/powerbi/api",
				client_id => $this->clientId,
				username => $this->reportUser->userName, 
				password => $this->reportUser->password 
				)
			)
		);	    
		$response = curl_exec($curl);
		$cError = curl_error($curl);
		if(curl_error($curl)) {
			echo "cURL Error #getEmbedToken:" . $cError;
			return ;
		}
		$result = json_decode($response, true);
		$token = $result["access_token"];
		$this->embedToken = "Bearer " . ' ' . $token;
	}

	private function getRest($urlType)   //reports or dashboards
	{
		if(empty($this->embedToken)) $this->getEmbedToken();

		//echo "<br>getting ".$this->groupId." REST data: ".$urlType." <br>";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'https://api.powerbi.com/v1.0/myorg/groups/'.$this->groupId.'/'.$urlType.'/');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_ENCODING, "");
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt ($curl, CURLOPT_HTTPHEADER,array(
				'Authorization:'.$this->embedToken,
				'Cache-Control: no-cache'
				));

		$resp = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if($err) 
		{
			echo "cURL Error #getRest:" . $err;
			return "";
		} 
		else 
		{
			$resp = json_decode($resp, true);
			if( $urlType == "reports")
			{
				$map = function($data) {
					return new Report($this, $data);
				};

				$this->reports = array_map($map, $resp['value']);
			}
			else //dashboard
			{
				$this->dashboard = new Report($this, $resp['value'][0]);
			}
		}
	}
};

?>
