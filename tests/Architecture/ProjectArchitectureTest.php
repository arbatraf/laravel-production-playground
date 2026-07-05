<?php

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectArchitectureTest extends TestCase
{
    private const array PRODUCTION_PATHS = [
        'app',
        'bootstrap/app.php',
        'bootstrap/providers.php',
        'config',
        'database',
        'resources',
        'routes',
    ];

    private const array DEBUG_HELPERS = [
        'dd',
        'dump',
        'ray',
    ];

    private const array FORBIDDEN_MOONSHINE_THREE_REFERENCES = [
        'MoonShine\\Actions\\',
        'MoonShine\\Components\\',
        'MoonShine\\Decorations\\',
        'MoonShine\\Fields\\',
        'MoonShine\\Metrics\\',
        'MoonShine\\Pages\\',
        'MoonShine\\Resources\\',
    ];

    public function test_frontend_tooling_uses_yarn_only(): void
    {
        $root = self::projectRoot();

        $this->assertFileExists($root.'/yarn.lock');

        foreach (['package-lock.json', 'npm-shrinkwrap.json', 'pnpm-lock.yaml'] as $lockFile) {
            $this->assertFileDoesNotExist($root.'/'.$lockFile, 'Unexpected non-Yarn lock file: '.$lockFile);
        }

        $package = self::readJson($root.'/package.json');
        $packageManager = $package['packageManager'] ?? null;

        $this->assertIsString($packageManager);
        $this->assertStringStartsWith('yarn@', $packageManager);

        $violations = [];
        $jsonScriptFiles = [
            'composer.json' => self::readJson($root.'/composer.json')['scripts'] ?? [],
            'package.json' => $package['scripts'] ?? [],
        ];

        foreach ($jsonScriptFiles as $file => $scripts) {
            foreach (self::flattenCommands($scripts) as $command) {
                if (preg_match('/(^|[^\w.-])(npm|npx|pnpm)(?=$|[^\w.-])/', $command) === 1) {
                    $violations[] = $file.': '.$command;
                }
            }
        }

        foreach (self::ciWorkflowFiles() as $file) {
            foreach (file($file) ?: [] as $line => $content) {
                if (preg_match('/\b(npm|npx|pnpm)\b/', $content) === 1) {
                    $violations[] = self::relativePath($file).':'.($line + 1).' '.$content;
                }
            }
        }

        $this->assertSame([], $violations, "Use Yarn commands only:\n".implode('', $violations));
    }

    public function test_production_php_paths_do_not_contain_debug_helpers(): void
    {
        $violations = [];

        foreach (self::productionPhpFiles() as $file) {
            $violations = [
                ...$violations,
                ...self::debugHelperViolations($file),
            ];
        }

        $this->assertSame([], $violations, "Remove debug helpers from production paths:\n".implode("\n", $violations));
    }

    public function test_project_code_does_not_use_moonshine_three_references(): void
    {
        $violations = self::moonShineComposerViolations();

        foreach (self::productionPhpFiles() as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            foreach (self::FORBIDDEN_MOONSHINE_THREE_REFERENCES as $reference) {
                if (str_contains($content, $reference)) {
                    $violations[] = self::relativePath($file).': uses '.$reference;
                }
            }
        }

        $this->assertSame([], $violations, "Use MoonShine 4 namespaces and packages only:\n".implode("\n", $violations));
    }

    /**
     * @return list<string>
     */
    private static function productionPhpFiles(): array
    {
        return self::files(self::PRODUCTION_PATHS, ['.php', '.blade.php']);
    }

    /**
     * @return list<string>
     */
    private static function ciWorkflowFiles(): array
    {
        return self::files(['.github/workflows'], ['.yml', '.yaml']);
    }

    /**
     * @param  list<string>  $paths
     * @param  list<string>  $suffixes
     * @return list<string>
     */
    private static function files(array $paths, array $suffixes): array
    {
        $files = [];
        $root = self::projectRoot();

        foreach ($paths as $path) {
            $absolutePath = $root.'/'.$path;

            if (! file_exists($absolutePath)) {
                continue;
            }

            if (is_file($absolutePath)) {
                if (self::hasAllowedSuffix($absolutePath, $suffixes)) {
                    $files[] = $absolutePath;
                }

                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absolutePath, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $pathname = $file->getPathname();

                if (self::hasAllowedSuffix($pathname, $suffixes)) {
                    $files[] = $pathname;
                }
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @param  list<string>  $suffixes
     */
    private static function hasAllowedSuffix(string $path, array $suffixes): bool
    {
        foreach ($suffixes as $suffix) {
            if (str_ends_with($path, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function debugHelperViolations(string $file): array
    {
        if (str_ends_with($file, '.blade.php')) {
            return self::bladeDebugHelperViolations($file);
        }

        return self::phpDebugHelperViolations($file);
    }

    /**
     * @return list<string>
     */
    private static function bladeDebugHelperViolations(string $file): array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return [];
        }

        preg_match_all('/@(?:dd|dump)\s*\(|(?<![\w\\\\>:])\b(?:dd|dump|ray)\s*\(/', $content, $matches, PREG_OFFSET_CAPTURE);

        return array_map(
            fn (array $match): string => self::relativePath($file).':'.self::lineNumber($content, $match[1]).' '.$match[0],
            $matches[0]
        );
    }

    /**
     * @return list<string>
     */
    private static function phpDebugHelperViolations(string $file): array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return [];
        }

        $violations = [];
        $tokens = token_get_all($content);

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            $function = self::debugFunctionName($token);

            if ($function === null || ! self::isFunctionCall($tokens, $index)) {
                continue;
            }

            $violations[] = self::relativePath($file).':'.$token[2].' '.$function.'()';
        }

        return $violations;
    }

    /**
     * @param  array{0: int, 1: string, 2: int}  $token
     */
    private static function debugFunctionName(array $token): ?string
    {
        $name = null;

        if ($token[0] === T_STRING) {
            $name = strtolower($token[1]);
        }

        if ($token[0] === T_NAME_FULLY_QUALIFIED) {
            $name = ltrim(strtolower($token[1]), '\\');
        }

        if ($name === null || ! in_array($name, self::DEBUG_HELPERS, true)) {
            return null;
        }

        return $name;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private static function isFunctionCall(array $tokens, int $index): bool
    {
        $next = self::nextMeaningfulToken($tokens, $index);

        if ($next !== '(') {
            return false;
        }

        $previous = self::previousMeaningfulToken($tokens, $index);

        if (! is_array($previous)) {
            return true;
        }

        return ! in_array($previous[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private static function previousMeaningfulToken(array $tokens, int $index): mixed
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (! self::isSkippableToken($tokens[$i])) {
                return $tokens[$i];
            }
        }

        return null;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private static function nextMeaningfulToken(array $tokens, int $index): mixed
    {
        $count = count($tokens);

        for ($i = $index + 1; $i < $count; $i++) {
            if (! self::isSkippableToken($tokens[$i])) {
                return $tokens[$i];
            }
        }

        return null;
    }

    private static function isSkippableToken(mixed $token): bool
    {
        return is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
    }

    /**
     * @return list<string>
     */
    private static function moonShineComposerViolations(): array
    {
        $composer = self::readJson(self::projectRoot().'/composer.json');
        $packages = [
            ...($composer['require'] ?? []),
            ...($composer['require-dev'] ?? []),
        ];

        $violations = [];

        foreach ($packages as $package => $constraint) {
            if (! is_string($package) || ! is_string($constraint) || ! str_starts_with($package, 'moonshine/')) {
                continue;
            }

            if (preg_match('/(?:^|[|,\s])(?:\^|~|=|v)?[0-3](?:\.|$)|<\s*4/', $constraint) === 1) {
                $violations[] = 'composer.json: '.$package.' '.$constraint;
            }
        }

        return $violations;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $file): array
    {
        $content = file_get_contents($file);

        self::assertIsString($content);

        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        self::assertIsArray($data);

        return $data;
    }

    /**
     * @return list<string>
     */
    private static function flattenCommands(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $commands = [];

        foreach ($value as $item) {
            $commands = [
                ...$commands,
                ...self::flattenCommands($item),
            ];
        }

        return $commands;
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function relativePath(string $path): string
    {
        return str_replace(self::projectRoot().'/', '', $path);
    }

    private static function lineNumber(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }
}
