<?php
namespace CafeCore\Dashboard;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-metrics.php';
require_once __DIR__ . '/class-store-data.php';
require_once __DIR__ . '/class-dashboard-page.php';

DashboardPage::register();
