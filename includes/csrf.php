<?php
declare(strict_types=1);
function csrf_token(): string { if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { $got=$_POST['_csrf'] ?? ''; if(!is_string($got)||!hash_equals(csrf_token(),$got)){http_response_code(419); exit('The form expired. Go back, reload, and try again.');} }
