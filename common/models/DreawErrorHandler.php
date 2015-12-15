<?php

	// EROR TYPE FOR OWN THROW IS 555
	class DreawErrorHandler extends Exception {

		private	$_debug = 0;

		public function __construct($msg, $set_handler = 1, $debug = DEBUG_MODE) {
			$this->_debug 				= $debug;
			$this->_log_file_fatal	 	= LOG_FILE_FATAL;
			$this->_log_file_warning	= LOG_FILE_WARNING;
			$this->_log_file_own		= LOG_FILE_OWN;
			$this->_filename_log 		= LOG_FILE;
			$this->_set_handler			= $set_handler;
			$this->_internal			= 0;

			if($this->_set_handler == 0) {
				self::SetHandle();

				if(($this->_log_file_fatal == 1 || $this->_log_file_warning == 1 || $this->_log_file_own == 1) && file_exists($this->_filename_log) === false)
					die('SOUBOR PRO LOGOVÁNÍ CHYB NEEXISTUJE, NEBO NEBYL NASTAVEN');
			} else {
				self::AutoHandle(555, $msg, $this->getFile(), $this->getLine());
			}


		}

		private function SetHandle() {
			error_reporting(E_ALL | E_STRICT);
			if(ini_get('display_errors')) {
				ini_set('display_startup_errors', 0);
				ini_set('display_errors', -1);
			} else {
				throw new Exception('INI_SET nelze použít');
			}
			
			set_error_handler(array($this, 'AutoHandle'));
			set_exception_handler(array($this, 'AutoHandle'));
			register_shutdown_function(array($this, 'FatalCatch'));

			$this->_set_handler = 1;
		}

		public function AutoHandle($errType = null, $errMsg = null, $errFile = null, $errLine = null) {
			switch($errType) {
				case E_WARNING:
					self::WarningErrorHandler($errMsg, $errFile, $errLine);
				break;
				case E_NOTICE:
					self::WarningErrorHandler($errMsg, $errFile, $errLine);
				break;
				case E_DEPRECATED:
					self::WarningErrorHandler($errMsg, $errFile, $errLine);
				break;
				case E_RECOVERABLE_ERROR:
					self::FatalErrorHandler($errMsg, $errFile, $errLine);
				break;
				case E_ERROR: 
					self::FatalErrorHandler($errMsg, $errFile, $errLine);
				break;
				case 555: 
					self::OwnMessageHandler($errMsg, $errFile, $errLine);
				break;
				default:
					self::FatalErrorHandler($errMsg, $errFile, $errLine);
				break;
			}
		}

		private function FatalErrorHandler($errMsg, $errFile, $errLine) {
			$context = ($this->_debug == 1) ? self::TreatError($errMsg, $errFile, self::RenderFile($errFile, $errLine)) : self::DefaultError();
			if($this->_log_file_fatal == 1)
				self::LogToFile($errMsg, $errFile, $errLine);
			die($context);
		}

		private function OwnMessageHandler($errMsg, $errFile, $errLine) {
			$context = ($this->_debug == 1) ? self::TreatError($errMsg, $errFile, self::RenderFile($errFile, $errLine)) : self::DefaultError();
			if($this->_log_file_own == 1)
				self::LogToFile($errMsg, $errFile, $errLine);
			die($context);
		}

		private function DefaultError() {
			header('Location: /error');
			exit(0);
		}

		private function WarningErrorHandler($errMsg, $errFile, $errLine) {
			$context = ($this->_debug == 1) ? self::TreatError($errMsg, $errFile, self::RenderFile($errFile, $errLine)) : self::DefaultError();
			if($this->_log_file_warning == 1)
				self::LogToFile($errMsg, $errFile, $errLine);
			die($context);
		}

		public function LogToFile($errMsg, $errFile, $errLine) {
			if(file_exists($this->_filename_log)) {
				$date = new DateTime;
				$datetime = $date->format('j. n. Y - H:i:s');

				$content = file_get_contents($this->_filename_log).'['.$datetime.'] '.$errMsg.' in '.$errFile.' on line '.$errLine."\r\n";

				return file_put_contents($this->_filename_log, $content);
			} else {
				die('SOUBOR PRO LOGOVÁNÍ NEBYL NASTAVEN');
			}
		}

		private function TreatError($errMsg, $errFile, $code) {
			return '
				<div style="width: 90%; padding: 30px 5%; background: red; display: block; color: #fff; font-family: monospace; font-size: 18px; line-height: 28px;">
				'.$errMsg.'
				<span style="display: block; width: 100%; font-size: 13px;">'.$errFile.'</span>
				</div>'.$code.
				'';
		}

		public function FatalCatch() {
			$error      = error_get_last();
		    if($error !== NULL && $error['type'] === E_ERROR) {
		        self::AutoHandle(E_ERROR, $error['message'], $error['file'], $error['line']);
		    }
		}

		private function RenderFile($filename, $err_line) { 
	        if(file_exists($filename) && is_file($filename)) { 

	            $file = highlight_file($filename, true); 
	            $array = explode('<br />', $file); 

	            $i = 1; 
	            $comments = false;

	            $code = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"font-family: monospace; background: #f0f0f0; padding: 25px 0 10px; display: block;\">\r\n"; 

	            foreach($array as $line) { 

	            	if($i >= $err_line - NUMBERS_OF_OUT_LINES && $i <= $err_line + NUMBERS_OF_OUT_LINES) {
	            		$background = $i == $err_line ? 'red; color: #fff; padidng: 10px 0' : 'none';
	                	$code .= '<tr style="background: '.$background.'"">' . "\r\n"; 

	                	$color = $i == $err_line ? '#fff; padding: 5px 20px!important' : '#666';
	                    $code .= '<td width="65px" nowrap style="color: '.$color.'; text-align: right; padding: 0 20px;">' . $i . ":</td>\r\n"; 

	                    if((strstr($line, '<span style="color: #FF8000">/*') !== false) && (strstr($line, '*/') !== false)) {

	                        $comments = false; 
	                        $startcolor = "orange"; 

	                    } elseif(strstr($line, '<span style="color: #FF8000">/*') !== false) {

	                        $comments = true; 

	                    } else {

	                        if($comments) {

	                            if(strstr($line, '*/') !== false) { 
	                                $comments = false; 
	                                $startcolor = "orange"; 
	                            } else { 
	                                $comments = true; 
	                                $startcolor = "green";
	                            }   
	                        } else { 
	                            $comments = false; 
	                            $startcolor = "green"; 
	                        }  

	                    }

	                    if($comments) 
	                        $startcolor = "orange";

	                    if($err_line == $i) {
	                    	$startcolor = "#fff; padding: 5px 20px!important";
							$line = preg_replace('#(<[a-z ]*)(style=("|\')(.*?)("|\'))([a-z ]*>)#', '\\1\\6', $line);
	                    }
	                    $code .= '<td width="100%" nowrap style="color: ' . $startcolor . '; padding: 0 20px;">' . $line . "</td>\r\n</tr>\r\n";

	                    $code .= '</tr>' . "\r\n"; 
	                }

	                $i++;
	            }

	            return $code.'</table>' . "\r\n"; 
	        }  
	    } 

	}


?>