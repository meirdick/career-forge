<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cover Letter — {{ $header['name'] ?? 'Cover Letter' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 48px 60px;
        }
        .contact-header {
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #ddd;
        }
        .contact-header h1 {
            font-size: 20pt;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
        }
        .contact-info {
            font-size: 9pt;
            color: #555;
            line-height: 1.6;
        }
        .cover-letter-body {
            margin-top: 24px;
            white-space: pre-wrap;
            line-height: 1.7;
        }
        .cover-letter-body p {
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="contact-header">
        <h1>{{ $header['name'] }}</h1>
        <div class="contact-info">
            {{ implode(' | ', array_filter([
                $header['email'] ?? null,
                $header['phone'] ?? null,
                $header['location'] ?? null,
                $header['linkedin_url'] ?? null,
                ...array_map(fn ($link) => $link['label'], $header['portfolio_links'] ?? []),
            ])) }}
        </div>
    </div>

    <div class="cover-letter-body">
        {!! Str::markdown($coverLetter, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]) !!}
    </div>
</body>
</html>
