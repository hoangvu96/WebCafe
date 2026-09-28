import { defineConfig, devices } from '@playwright/test';

for (const name of ['STAFF_USER', 'STAFF_PASSWORD']) {
	if (!process.env[name]) {
		throw new Error(`Thiếu biến môi trường ${name} (chạy qua scripts/test-e2e.sh để nạp .env).`);
	}
}

export default defineConfig({
	testDir: './specs',
	timeout: 60_000,
	workers: 1, // dùng chung DB và tồn kho
	retries: 0,
	globalTeardown: './global-teardown.ts',
	reporter: [['list'], ['html', { open: 'never' }]],
	use: {
		baseURL: process.env.WP_URL ?? 'http://localhost:8080',
		locale: 'vi-VN',
		trace: 'retain-on-failure',
	},
	projects: [
		{ name: 'desktop', use: { ...devices['Desktop Chrome'] } },
		{ name: 'mobile', use: { ...devices['Pixel 7'] }, testMatch: /(checkout|shipping)\.spec\.ts/ },
	],
});
