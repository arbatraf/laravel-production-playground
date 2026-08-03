import { expect, test } from '@playwright/test';
import { backofficeUrls, loginBackoffice } from './support/backoffice.mjs';

test('guest is redirected to the backoffice login', async ({ page }) => {
    await page.goto(backofficeUrls.dashboard);

    await expect(page).toHaveURL('/backoffice/login');
    await expect(page.getByText('Backoffice', { exact: true })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Email *', exact: true })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Password *', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Log in', exact: true })).toBeVisible();
});

test('admin can open company, task and user pages', async ({ page }) => {
    await loginBackoffice(page, 'admin@example.com');

    await expect(page.getByRole('button', { name: 'Operations', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Administration', exact: true })).toBeVisible();

    await page.goto(backofficeUrls.companies);
    await expect(page.getByRole('heading', { name: 'Companies', exact: true })).toBeVisible();

    await page.goto(backofficeUrls.tasks);
    await expect(page.getByRole('heading', { name: 'Tasks', exact: true })).toBeVisible();

    await page.goto(backofficeUrls.users);
    await expect(page.getByRole('heading', { name: 'Users', exact: true })).toBeVisible();
});

test('manager can open the task create page but not the user index', async ({ page }) => {
    await loginBackoffice(page, 'manager@example.com');

    await expect(page.getByRole('button', { name: 'Operations', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Administration', exact: true })).toHaveCount(0);

    const tasks = await page.context().request.get(backofficeUrls.tasks, {
        maxRedirects: 0,
    });
    const taskCreate = await page.context().request.get(backofficeUrls.taskCreate, {
        maxRedirects: 0,
    });
    const users = await page.context().request.get(backofficeUrls.users, {
        maxRedirects: 0,
    });

    expect(tasks.status()).toBe(200);
    expect(taskCreate.status()).toBe(200);
    expect(users.status()).toBe(403);
});

test('viewer can open company and task pages but not the task create or user pages', async ({ page }) => {
    await loginBackoffice(page, 'viewer@example.com');

    await expect(page.getByRole('button', { name: 'Operations', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Administration', exact: true })).toHaveCount(0);

    await page.goto(backofficeUrls.companies);
    await expect(page.getByRole('heading', { name: 'Companies', exact: true })).toBeVisible();
    await expect(page.getByText('Acme Logistics', { exact: true })).toBeVisible();

    const tasks = await page.context().request.get(backofficeUrls.tasks, {
        maxRedirects: 0,
    });
    const taskCreate = await page.context().request.get(backofficeUrls.taskCreate, {
        maxRedirects: 0,
    });
    const users = await page.context().request.get(backofficeUrls.users, {
        maxRedirects: 0,
    });

    expect(tasks.status()).toBe(200);
    expect(taskCreate.status()).toBe(403);
    expect(users.status()).toBe(403);
});
