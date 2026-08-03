import { expect } from '@playwright/test';

export const backofficeUrls = {
    dashboard: '/backoffice',
    companies: '/backoffice/resource/company-resource/company-index-page',
    tasks: '/backoffice/resource/task-resource/task-index-page',
    taskCreate: '/backoffice/resource/task-resource/task-form-page',
    users: '/backoffice/resource/user-resource/user-index-page',
};

export async function loginBackoffice(page, email) {
    await page.goto('/backoffice/login');
    await page.getByRole('textbox', { name: 'Email *', exact: true }).fill(email);
    await page.getByRole('textbox', { name: 'Password *', exact: true }).fill('password');

    await Promise.all([
        page.waitForURL(backofficeUrls.dashboard),
        page.getByRole('button', { name: 'Log in', exact: true }).click(),
    ]);

    await expect(page.getByRole('heading', { name: 'Dashboard', exact: true })).toBeVisible();
}
