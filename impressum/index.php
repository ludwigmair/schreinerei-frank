<?php
/**
 * Impressum – nutzt die gemeinsamen Partial-Templates aus /php/.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/php/bootstrap.php';

$page = 'impressum';
$baseHref = '../';
include dirname(__DIR__) . '/php/header.php';
include dirname(__DIR__) . '/php/legal.php';
include dirname(__DIR__) . '/php/footer.php';
