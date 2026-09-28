import { expect, type Page } from '@playwright/test';

export const PRODUCT_PATH = '/san-pham/robusta-buon-ma-thuot/';

export async function addRobustaToCart(page: Page, qty = 1): Promise<void> {
	await page.goto(PRODUCT_PATH);
	await page.selectOption('#pa_khoi-luong', '250g');
	await page.selectOption('#pa_dang-xay', 'xay-phin');
	await page.fill('form.cart input.qty', String(qty));
	await Promise.all([
		page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes(PRODUCT_PATH)),
		page.click('button.single_add_to_cart_button'),
	]);
}

export async function waitForCheckoutIdle(page: Page): Promise<void> {
	await expect(page.locator('.blockUI')).toHaveCount(0);
}

export async function fillCheckout(page: Page, phone = '0912345678'): Promise<void> {
	await page.goto('/thanh-toan/');
	await page.fill('#billing_first_name', 'Nguyễn Văn An');
	await page.fill('#billing_phone', phone);
	await page.fill('#billing_city', 'Đắk Lắk');
	await page.fill('#billing_ward', 'Phường Buôn Ma Thuột');
	await page.fill('#billing_address_1', '12 Lê Duẩn');
	await waitForCheckoutIdle(page);
}

/** Đặt đơn COD và trả về mã đơn. */
export async function placeCodOrder(page: Page): Promise<string> {
	await fillCheckout(page);
	await page.click('#place_order');
	await page.waitForURL(/order-received\/(\d+)/);
	return page.url().match(/order-received\/(\d+)/)![1];
}

export async function loginAsStaff(page: Page): Promise<void> {
	await page.goto('/wp-login.php');
	await page.fill('#user_login', process.env.STAFF_USER!);
	await page.fill('#user_pass', process.env.STAFF_PASSWORD!);
	await Promise.all([page.waitForURL(/page=cafe-dashboard/), page.click('#wp-submit')]);
}
