import { expect, test } from '@playwright/test';
import { addRobustaToCart, loginAsStaff, placeCodOrder, PRODUCT_PATH } from '../helpers';

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

	await page.goto(`/wp-admin/admin.php?page=wc-orders&action=edit&id=${orderId}`);
	await expect(page.locator('#order_status')).toHaveValue('wc-completed');
});

test('nhân viên xem được Tồn kho', async ({ page }) => {
	await loginAsStaff(page);
	await page.goto('/wp-admin/admin.php?page=cafe-inventory');
	await expect(page.locator('.wrap')).toContainText('Robusta Buôn Ma Thuột');
});

test('nhân viên không vào được cài đặt và không sửa được sản phẩm', async ({ page }) => {
	await page.goto(PRODUCT_PATH);
	const productId = await page.locator('form.cart input[name="product_id"]').inputValue(); // Robusta Buôn Ma Thuột (CF-ROB-BMT)
	expect(productId).toMatch(/^\d+$/);

	await loginAsStaff(page);
	for (const path of [
		`/wp-admin/post.php?post=${productId}&action=edit`,
		'/wp-admin/admin.php?page=wc-settings',
		'/wp-admin/admin.php?page=cafe-settings',
		'/wp-admin/post-new.php?post_type=product',
		'/wp-admin/edit.php?post_type=product',
	]) {
		const response = await page.goto(path);
		expect(response?.status(), path).toBe(403);
	}
});
