import { spawn } from 'node:child_process';
import net from 'node:net';
import { once } from 'node:events';
import { setTimeout as delay } from 'node:timers/promises';
import assert from 'node:assert/strict';
import test from 'node:test';

const root = new URL('../../', import.meta.url);

async function openPort() {
    const server = net.createServer();

    await new Promise((resolve, reject) => {
        server.once('error', reject);
        server.listen(0, '127.0.0.1', resolve);
    });

    const address = server.address();

    await new Promise((resolve, reject) => {
        server.once('error', reject);
        server.close(resolve);
    });

    assert.equal(typeof address, 'object');
    assert.notEqual(address, null);

    return address.port;
}

async function waitForHttp(url, server) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < 15_000) {
        if (server.exitCode !== null) {
            throw new Error(`Laravel server exited with code ${server.exitCode}.`);
        }

        try {
            const response = await fetch(url);

            if (response.ok) {
                return;
            }
        } catch {
            await delay(250);
        }
    }

    throw new Error(`Laravel server did not respond at ${url}.`);
}

async function stopServer(server) {
    if (server.exitCode !== null || server.signalCode !== null) {
        return;
    }

    const pid = server.pid;
    const signalTarget = process.platform === 'win32' ? pid : -pid;

    try {
        process.kill(signalTarget, 'SIGTERM');
    } catch {
        return;
    }

    const exited = await Promise.race([
        once(server, 'exit').then(() => true),
        delay(3_000).then(() => false),
    ]);

    if (! exited) {
        process.kill(signalTarget, 'SIGKILL');
        await once(server, 'exit');
    }
}

test('foundation routes respond over http', async () => {
    const port = await openPort();
    const baseUrl = `http://127.0.0.1:${port}`;
    const server = spawn('./scripts/php', [
        'artisan',
        'serve',
        '--host=127.0.0.1',
        `--port=${port}`,
    ], {
        cwd: root,
        env: {
            ...process.env,
            APP_ENV: 'testing',
        },
        detached: process.platform !== 'win32',
        stdio: ['ignore', 'ignore', 'pipe'],
    });

    const errors = [];

    server.stderr.on('data', chunk => errors.push(chunk.toString()));

    try {
        await waitForHttp(`${baseUrl}/api/v1/health`, server);

        const home = await fetch(baseUrl);
        const health = await fetch(`${baseUrl}/api/v1/health`);

        assert.equal(home.status, 200);
        assert.equal(health.status, 200);
        assert.deepEqual(await health.json(), { status: 'ok' });
    } catch (error) {
        if (errors.length > 0) {
            error.message += `\n${errors.join('')}`;
        }

        throw error;
    } finally {
        await stopServer(server);
    }
});
