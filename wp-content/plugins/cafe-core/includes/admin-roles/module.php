<?php
namespace CafeCore\AdminRoles;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-roles.php';
require_once __DIR__ . '/class-admin-cleanup.php';
require_once __DIR__ . '/class-branding.php';

Roles::register();
AdminCleanup::register();
Branding::register();
