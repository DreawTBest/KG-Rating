<?php

	class CronController extends RouterController {
		public function treat($parameters) {
			$cron = new DreawCron();
			$cron->takeSnapshot();
		}
	}