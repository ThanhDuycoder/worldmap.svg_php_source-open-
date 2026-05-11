<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/settings.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/config.php';

requireAdmin();

// Values come from DB settings first, fallback to env/constants.
$out = [
    'gemini_model' => settingGet('gemini_model', geminiModel()),
    'gemini_temperature' => settingGet('gemini_temperature', '0.4'),
    'gemini_max_output_tokens' => settingGet('gemini_max_output_tokens', '4096'),
];

ok($out);

