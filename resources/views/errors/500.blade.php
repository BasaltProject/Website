<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111216">
    <title>500 Something went wrong. - Basalt</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { color-scheme: dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: #111216;
            color: #f0f1f3;
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            text-align: center;
            -webkit-font-smoothing: antialiased;
        }
        img { display: block; width: auto; height: 2.75rem; margin: 0 auto 3rem; }
        .code { margin: 0; color: #e5484d; font-size: clamp(5rem, 16vw, 9rem); font-weight: 600; line-height: 1; letter-spacing: -.05em; }
        h1 { margin: 1.5rem 0 0; font-size: clamp(1.7rem, 3.6vw, 2.4rem); font-weight: 600; line-height: 1.2; letter-spacing: -.025em; }
        p.msg { max-width: 28rem; margin: 1rem auto 0; color: #b4b9c3; line-height: 1.6; }
        a.btn {
            display: inline-block;
            margin-top: 2.25rem;
            padding: .8rem 1.5rem;
            border: 1px solid #ef5569;
            border-radius: .5rem;
            background: #ef5569;
            color: #14100d;
            font-size: .92rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color .15s ease, border-color .15s ease;
        }
        a.btn:hover { border-color: #f96d80; background: #f96d80; }
        a.btn:focus-visible { outline: 2px solid #ef5569; outline-offset: 3px; }
    </style>
</head>
<body>
    <img src="/basalt-logo.svg" alt="Basalt" width="1220" height="450">
    <p class="code">500</p>
    <h1>Something went wrong.</h1>
    <p class="msg">The server hit an unexpected error. Try again in a moment.</p>
    <a href="/" class="btn">Back to home</a>
</body>
</html>
