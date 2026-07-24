import { defineConfig, devices } from '@playwright/test';

const port = process.env.PLAYWRIGHT_PORT ?? '8017';

if (! /^\d+$/.test(port)) {
    throw new Error('PLAYWRIGHT_PORT must be numeric.');
}

const baseURL = `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: './tests/e2e/browser',
    outputDir: './test-results/playwright',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    timeout: 30_000,
    expect: {
        timeout: 10_000,
    },
    reporter: process.env.CI ? [['github'], ['line']] : 'line',
    use: {
        ...devices['Desktop Chrome'],
        baseURL,
        headless: true,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        video: 'retain-on-failure',
    },
    webServer: {
        command: `./scripts/php artisan serve --host=127.0.0.1 --port=${port} --tries=1 --no-reload --no-interaction`,
        url: `${baseURL}/api/v1/health`,
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
