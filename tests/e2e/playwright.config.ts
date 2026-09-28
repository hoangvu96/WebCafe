import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
	testDir: './specs',
	timeout: 60_000,
	workers: 1, // dùng chung DB và tồn kho
	retries: 0,
	reporter: [['list'], ['html', { open: 'never' }]],
	use: {
		baseURL: process.env.WP_URL ?? 'http://localhost:8080',
		locale: 'vi-VN',
		trace: 'retain-on-failure',
	},
	projects: [
		{ name: 'desktop', use: { ...devices['Desktop Chrome'] } },
		{ name: 'mobile', use: { ...devices['Pixel 7'] }, testMatch: /checkout\.spec\.ts/ },
	],
});
