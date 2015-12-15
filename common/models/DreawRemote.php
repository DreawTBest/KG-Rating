<?php

	class DreawRemote {

		public function remoteFileExists($url) {
		    $curl = curl_init($url);
		    curl_setopt($curl, CURLOPT_NOBODY, true);
		    $result = curl_exec($curl);

		    $ret = false;

		    if ($result !== false) {
		        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		        if ($statusCode == 200) {
		            $ret = true;
		        }
		    }

		    curl_close($curl);

		    return $ret;
		}
	}

?>