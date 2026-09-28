import { expect, test } from '@playwright/test';
import { addRobustaToCart, loginAsStaff, placeCodOrder } from '../helpers';

test('nhân viên đánh dấu Đã giao từ trang Tổng quan', async ({ browser, page }) => {
	const customer = await browser.newPage();
	await addRobustaToCart(customer);
	const orderId = await placeCodOrder(customer);
	await customer.close();

	await loginAsStaff(page);
	const row = page.locator(`tr[data-order-id="${orderId}"]`);
	await expect(row).toBeVisible();
	await row.getByRole('button', { name: 'Đã giao' }).click();
	await expect(row).toHaveCount(0);

	await page.reload();
	await expect(page.locator(`tr[data-order-id="${orderId}"]`)).toHaveCount(0);
});

test('nhân viên xem được Tồn kho', async ({ page }) => {
	await loginAsStaff(page);
	await page.goto('/wp-admin/admin.php?page=cafe-inventory');
	await expect(page.locator('.wrap')).toContainText('Robusta Buôn Ma Thuột');
});

test('nhân viên không vào được cài đặt và không sửa được sản phẩm', async ({ page }) => {
	await loginAsStaff(page);
	for (const path of [
		'/wp-admin/admin.php?page=wc-settings',
		'/wp-admin/admin.php?page=cafe-settings',
		'/wp-admin/post-new.php?post_type=product',
		'/wp-admin/edit.php?post_type=product',
	]) {
		const response = await page.goto(path);
		expect(response?.status(), path).toBe(403);
	}
});
