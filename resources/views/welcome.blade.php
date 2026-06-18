<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'PEL Quispicanchi al 2036') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <style>
            :root {
                --amber-600: #d97706;
                --amber-700: #b45309;
                --neutral-50: #fafafa;
                --neutral-200: #e5e5e5;
                --neutral-600: #525252;
                --neutral-900: #171717;
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                background: var(--neutral-50);
                color: var(--neutral-900);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }

            header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 1.5rem 2rem;
                border-bottom: 1px solid var(--neutral-200);
            }

            .brand {
                font-weight: 700;
                font-size: 1.125rem;
            }

            .brand small {
                display: block;
                font-weight: 400;
                font-size: 0.75rem;
                color: var(--neutral-600);
            }

            .btn {
                display: inline-block;
                padding: 0.6rem 1.4rem;
                background: var(--amber-600);
                color: #fff;
                text-decoration: none;
                border-radius: 0.375rem;
                font-weight: 600;
                font-size: 0.9rem;
                transition: background 0.15s;
            }

            .btn:hover { background: var(--amber-700); }

            main {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 3rem 1.5rem;
            }

            .hero {
                max-width: 720px;
                text-align: center;
            }

            .hero h1 {
                font-size: 2.25rem;
                line-height: 1.2;
                margin: 0 0 0.75rem;
            }

            .hero p {
                color: var(--neutral-600);
                font-size: 1.05rem;
                line-height: 1.6;
                margin: 0 0 2rem;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
                margin-bottom: 2.5rem;
            }

            .stat {
                background: #fff;
                border: 1px solid var(--neutral-200);
                border-radius: 0.5rem;
                padding: 1.25rem 1rem;
            }

            .stat strong {
                display: block;
                font-size: 1.5rem;
                color: var(--amber-600);
            }

            .stat span {
                font-size: 0.8rem;
                color: var(--neutral-600);
            }

            footer {
                padding: 1.5rem 2rem;
                text-align: center;
                font-size: 0.8rem;
                color: var(--neutral-600);
                border-top: 1px solid var(--neutral-200);
            }
        </style>
    </head>
    <body>
        <header>
            <div class="brand">
                PEL Quispicanchi al 2036
                <small>Asociación Civil Edutalento</small>
            </div>
            <a href="{{ url('/admin') }}" class="btn">Ingresar al panel</a>
        </header>

        <main>
            <div class="hero">
                <h1>Sistema de Indicadores Educativos de Quispicanchi</h1>
                <p>
                    Plataforma para procesar, unificar y analizar la información estadística
                    e indicadores educativos de los 12 distritos de la provincia de Quispicanchi,
                    como parte del proyecto "PEL Quispicanchi al 2036".
                </p>

                <div class="stats">
                    <div class="stat">
                        <strong>12</strong>
                        <span>Distritos</span>
                    </div>
                    <div class="stat">
                        <strong>2022-2026</strong>
                        <span>Periodo histórico</span>
                    </div>
                    <div class="stat">
                        <strong>4</strong>
                        <span>Fuentes: UGEL, ESCALE, INEI, MIDIS</span>
                    </div>
                </div>
                <a href="{{ url('/admin') }}" class="btn">Ir al panel administrativo</a>
            </div>
        </main>

        <footer>
            Elaboración: Edutalento
        </footer>
    </body>
</html>
