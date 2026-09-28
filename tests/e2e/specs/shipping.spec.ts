import { expect, test } from '@playwright/test';
import { addRobustaToCart, fillCheckout } from '../helpers';

test('đơn dưới 500.000đ tính phí ship 30.000đ', async ({ page }) => {
	await addRobustaToCart(page, 1); // 95.000đ
	await fillCheckout(page);
	const shipping = page.locator('.woocommerce-shipping-totals');
	await expect(shipping).toContainText('Phí vận chuyển');
	await expect(shipping).toContainText('30.000');
});

test('đơn từ 500.000đ được miễn phí ship', async ({ page }) => {
	await addRobustaToCart(page, 6); // 570.000đ
	await fillCheckout(page);
	await expect(page.locator('.woocommerce-shipping-totals')).toContainText('Miễn phí vận chuyển');
});
