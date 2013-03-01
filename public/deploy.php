<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	shell_exec('sudo ../deploy.sh');
}
