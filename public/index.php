<?php require_once __DIR__.'/../includes/bootstrap.php'; if(current_user()) redirect(current_user()['role']==='ADMIN'?'/admin/dashboard.php':'/dashboard.php'); redirect('/login.php');
