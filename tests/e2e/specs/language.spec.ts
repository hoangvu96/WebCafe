import { expect, test, type Page } from '@playwright/test';
import { addRobustaToCart, fillCheckout } from '../helpers';

/** Chọn ngôn ngữ qua nút trên header (desktop hoặc mobile, tuỳ nút nào đang hiển thị). */
async function chooseLanguage(page: Page, label: 'Tiếng Việt' | 'English'): Promise<void> {
	const switcher = page.locator('.cafe-lang >> visible=true');
	const toggle = switcher.locator('.cafe-lang__toggle');
	await expect(toggle).toHaveAttribute('aria-expanded', 'false');
	await toggle.click();
	await expect(toggle).toHaveAttribute('aria-expanded', 'true');
	await Promise.all([page.waitForURL((url) => !url.search.includes('lang=')), switcher.getByRole('link', { name: label }).click()]);
}

test('mặc định là tiếng Việt, chuyển sang tiếng Anh rồi quay lại', async ({ page }) => {
	await addRobustaToCart(page, 1);
	await fillCheckout(page);
	await expect(page.locator('html')).toHaveAttribute('lang', 'vi');
	await expect(page.locator('label[for="billing_first_name"]')).toContainText('Họ và tên');

	await chooseLanguage(page, 'English');
	await expect(page).toHaveURL(/\/thanh-toan\/$/);
	await expect(page.locator('html')).toHaveAttribute('lang', 'en-US');
	await expect(page.locator('label[for="billing_first_name"]')).toContainText('Full name');
	await expect(page.locator('.woocommerce-shipping-totals')).toContainText('Shipping fee');
	await expect(page.locator('#place_order')).toHaveText(/Place order/i);

	// Lựa chọn được nhớ khi sang trang khác; chuỗi của child theme cũng được dịch.
	await page.goto('/cua-hang/');
	await expect(page.locator('html')).toHaveAttribute('lang', 'en-US');
	await expect(page.locator('.cafe-breadcrumb')).toContainText('Home');

	await chooseLanguage(page, 'Tiếng Việt');
	await expect(page.locator('html')).toHaveAttribute('lang', 'vi');
	await expect(page.locator('.cafe-lang__toggle >> visible=true')).toHaveAttribute('aria-label', /Tiếng Việt/);
});

test('nội dung trong DB (trừ sản phẩm) được dịch khi chọn English', async ({ page }) => {
	await page.goto('/');
	await expect(page.locator('h1').first()).toContainText('Cà phê rang mộc');
	await chooseLanguage(page, 'English');

	// Trang chủ, nút, footer.
	await expect(page.locator('h1').first()).toContainText('Honest roasts');
	await expect(page.getByRole('link', { name: 'Shop now' }).first()).toBeVisible();
	await expect(page.locator('footer')).toContainText('Policies');
	await expect(page.locator('footer')).toContainText('Pure roasted & ground coffee');
	await expect(page.locator('footer')).toContainText('Shipping policy');

	// Trang thường: tiêu đề và nội dung từ bản tiếng Anh, URL giữ nguyên.
	await page.goto('/chinh-sach-giao-hang/');
	await expect(page.locator('h1')).toHaveText('Shipping policy');
	await expect(page.locator('.entry-content')).toContainText('We deliver nationwide');
	await expect(page).toHaveTitle(/Shipping policy/);

	// Danh mục và thuộc tính dịch; tên sản phẩm giữ tiếng Việt.
	await page.goto('/danh-muc/hoa-tan/');
	await expect(page.locator('h1')).toHaveText('Instant');
	await page.goto('/san-pham/robusta-buon-ma-thuot/');
	await expect(page.locator('h1.product_title')).toHaveText('Robusta Buôn Ma Thuột');
	await expect(page.locator('label[for="pa_dang-xay"]')).toHaveText('Grind');
	await expect(page.locator('#pa_dang-xay option[value="xay-phin"]')).toHaveText('Phin grind');

	// Phương thức thanh toán.
	await addRobustaToCart(page, 1);
	await fillCheckout(page);
	await expect(page.locator('.wc_payment_method')).toContainText('Cash on delivery (COD)');

	// Quay lại tiếng Việt thì nội dung gốc hiện lại.
	await chooseLanguage(page, 'Tiếng Việt');
	await expect(page.locator('.wc_payment_method')).toContainText('Thanh toán khi nhận hàng');
});

test('menu chọn ngôn ngữ đóng khi bấm Escape', async ({ page }) => {
	await page.goto('/');
	const toggle = page.locator('.cafe-lang__toggle >> visible=true');
	await toggle.click();
	await expect(toggle).toHaveAttribute('aria-expanded', 'true');
	await page.keyboard.press('Escape');
	await expect(toggle).toHaveAttribute('aria-expanded', 'false');
	await expect(toggle).toBeFocused();
});
