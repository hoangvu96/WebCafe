<?php
namespace CafeCore\BeanInfo;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-bean-info.php';
require_once __DIR__ . '/class-bean-info-fields.php';

BeanInfoFields::register();
