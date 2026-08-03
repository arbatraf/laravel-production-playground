import { expect, test } from '@playwright/test';
import { backofficeUrls, loginBackoffice } from './support/backoffice.mjs';

test.describe.configure({ retries: 0 });

function taskRow(page, title) {
    return page.locator('tbody tr').filter({ hasText: title });
}

async function openActions(row) {
    const button = row.locator('button.dropdown-btn');

    await expect(button).toHaveCount(1);
    await button.click();
}

test.beforeEach(async ({ page }) => {
    await loginBackoffice(page, 'admin@example.com');
    await page.goto(backofficeUrls.tasks);
    await expect(page.getByRole('heading', { name: 'Tasks', exact: true })).toBeVisible();
});

test('direct status action enforces CSRF and refreshes the row', async ({ page }) => {
    const row = taskRow(page, 'Check contract renewal date');

    await expect(row.getByText('Open', { exact: true })).toBeVisible();
    await openActions(row);

    const start = row.getByRole('link', { name: 'Start', exact: true });
    const href = await start.getAttribute('href');

    expect(href).not.toBeNull();

    const actionUrl = new URL(href, page.url()).toString();
    const authenticated = await page.context().request.get(backofficeUrls.tasks, {
        maxRedirects: 0,
    });

    expect(authenticated.status()).toBe(200);

    const rejected = await page.context().request.post(actionUrl, {
        headers: {
            Accept: 'application/json',
            'Sec-Fetch-Site': 'cross-site',
            'X-Requested-With': 'XMLHttpRequest',
        },
        maxRedirects: 0,
    });

    expect(rejected.status()).toBe(419);
    await expect(row.getByText('Open', { exact: true })).toBeVisible();

    const responsePromise = page.waitForResponse(response => (
        response.url() === actionUrl
        && response.request().method() === 'POST'
    ));

    await start.click();

    const response = await responsePromise;

    expect(response.status()).toBe(200);
    await expect(row.getByText('In progress', { exact: true })).toBeVisible();
});

test('terminal status action submits the confirmation form with CSRF', async ({ page }) => {
    const row = taskRow(page, 'Confirm warehouse delivery window');

    await expect(row.getByText('In progress', { exact: true })).toBeVisible();
    await openActions(row);
    await row.getByRole('link', { name: 'Complete', exact: true }).click();

    const dialog = page.getByRole('dialog').filter({ hasText: 'Complete task?' });
    const form = dialog.locator('form');

    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('heading', { name: 'Complete task?', exact: true })).toBeVisible();
    await expect(form).toHaveCount(1);
    await expect(form).toHaveAttribute('method', 'post');
    await expect(form).toHaveAttribute('action', /\/handler\/task-complete\?task_id=\d+$/);
    await expect(form.locator('input[name="_token"]')).toHaveValue(/\S+/);

    const actionUrl = await form.getAttribute('action');

    expect(actionUrl).not.toBeNull();

    const responsePromise = page.waitForResponse(response => (
        response.url() === actionUrl
        && response.request().method() === 'POST'
    ));

    await dialog.getByRole('button', { name: 'Complete', exact: true }).click();

    const response = await responsePromise;

    expect(response.status()).toBe(200);
    await expect(dialog).toBeHidden();
    await expect(row.getByText('Done', { exact: true })).toBeVisible();
});
