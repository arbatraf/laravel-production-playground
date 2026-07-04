<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body>
        <main class="lpp-foundation">
            <section class="lpp-foundation__shell" aria-labelledby="foundation-title">
                <p class="lpp-foundation__eyebrow">Foundation installed</p>

                <h1 id="foundation-title" class="lpp-foundation__title">
                    Laravel Production Playground
                </h1>

                <p class="lpp-foundation__text">
                    Laravel 13 foundation for Laravel Production Playground.
                </p>

                <div class="lpp-foundation__meta" aria-label="Foundation status">
                    <div class="lpp-foundation__item">
                        <p class="lpp-foundation__label">Runtime</p>
                        <p class="lpp-foundation__value">PHP 8.5</p>
                    </div>

                    <div class="lpp-foundation__item">
                        <p class="lpp-foundation__label">Framework</p>
                        <p class="lpp-foundation__value">Laravel 13</p>
                    </div>

                    <div class="lpp-foundation__item">
                        <p class="lpp-foundation__label">Assets</p>
                        <p class="lpp-foundation__value">Vite + Yarn</p>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
