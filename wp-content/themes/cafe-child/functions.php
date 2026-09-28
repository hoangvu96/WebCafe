<?php
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'cafe-child-fonts',
			'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Lora:wght@500;600;700&display=swap',
			array(),
			null
		);
		// Priority 20: nạp sau CSS và biến màu inline của Kadence để ghi đè được.
		wp_enqueue_style(
			'cafe-child',
			get_stylesheet_directory_uri() . '/assets/css/cafe.css',
			array( 'cafe-child-fonts' ),
			wp_get_theme()->get( 'Version' )
		);
	},
	20
);
