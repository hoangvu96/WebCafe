import { expect, test } from '@playwright/test';
import { addRobustaToCart, fillCheckout, PRODUCT_PATH } from '../helpers';

test('trang sản phẩm có khung Thông tin hạt', async ({ page }) => {
	await page.goto(PRODUCT_PATH);
	const box = page.locator('.cafe-bean-info');
	await expect(box).toContainText('Nguồn gốc');
	await expect(box).toContainText('Buôn Ma Thuột, Đắk Lắk');
	await expect(box).toContainText('Đậm');
});

test('form thanh toán theo địa chỉ Việt Nam 2 cấp', async ({ page }) => {
	await addRobustaToCart(page);
	await fillCheckout(page);
	for (const id of ['#billing_last_name', '#billing_company', '#billing_postcode', '#billing_state', '#billing_address_2']) {
		await expect(page.locator(id)).toHaveCount(0);
	}
	await expect(page.locator('label[for="billing_city"]')).toContainText('Tỉnh/Thành phố');
	await expect(page.locator('label[for="billing_ward"]')).toContainText('Phường/Xã/Đặc khu');
	await expect(page.locator('#billing_email_field abbr.required')).toHaveCount(0);
	await expect(page.locator('#billing_country_field')).toBeHidden();
});

test('số điện thoại sai bị từ chối', async ({ page }) => {
	await addRobustaToCart(page);
	await fillCheckout(page, '12345');
	await page.click('#place_order');
	await expect(page.locator('.woocommerce-error')).toContainText('Số điện thoại không hợp lệ');
});

test('khách đặt đơn COD thành công', async ({ page }) => {
	await addRobustaToCart(page);
	await fillCheckout(page);
	await expect(page.locator('#payment_method_cod')).toBeChecked();
	await page.click('#place_order');
	await page.waitForURL(/order-received/);
	await expect(page.locator('.cafe-thankyou-cod')).toContainText('Vui lòng chuẩn bị');
	await expect(page.locator('.woocommerce-order-overview__payment-method')).toContainText('Thanh toán khi nhận hàng');
	await expect(page.locator('.woocommerce-customer-details')).toContainText('Phường Buôn Ma Thuột');
});
