<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';

clearAuthSession();
redirect('../../pages/auth/index.php');
