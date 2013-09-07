<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	echo shell_exec('sudo ../deploy.sh 2>&1');
}
