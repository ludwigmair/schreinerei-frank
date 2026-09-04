<?php
/**
 * Datenschutz – nutzt die gemeinsamen Partial-Templates aus /php/.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/php/bootstrap.php';

$page = 'datenschutz';
$baseHref = site_set_base('../');
include dirname(__DIR__) . '/php/header.php';
include dirname(__DIR__) . '/php/legal.php';
include dirname(__DIR__) . '/php/footer.php';
