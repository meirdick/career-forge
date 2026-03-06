<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $header['name'] ?? $user->name ?? 'Resume' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 10.5pt;
            line-height: 1.4;
            color: #1a1a1a;
        }
        .header {
            background: #1e3a5f;
            color: #fff;
            padding: 28px 48px 20px;
        }
        .header h1 {
            font-size: 24pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .header .contact-info {
            font-size: 9pt;
            color: #c8d6e5;
            line-height: 1.6;
        }
        .header .contact-info a {
            color: #c8d6e5;
            text-decoration: none;
        }
        .body-content {
            padding: 20px 48px 36px;
        }
        .section {
            margin-bottom: 16px;
        }
        .section h2 {
            font-size: 12pt;
            font-weight: 700;
            color: #1e3a5f;
            border-left: 3px solid #1e3a5f;
            padding-left: 10px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-content {
            font-size: 10.5pt;
            line-height: 1.45;
            padding-left: 13px;
        }
        .section-content p { margin-bottom: 4px; }
        .section-content ul { margin: 2px 0 6px 18px; }
        .section-content li { margin-bottom: 2px; }
        .section-content strong { font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $header['name'] ?? $user->name ?? 'Candidate' }}</h1>
        <div class="contact-info">
            @php
                $parts = array_filter([
                    $header['email'] ?? $user->email,
                    $header['phone'] ?? $user->phone,
                    $header['location'] ?? $user->location,
                    $header['linkedin_url'] ?? $user->linkedin_url,
                    $header['portfolio_url'] ?? $user->portfolio_url,
                ]);
            @endphp
            {{ implode(' | ', $parts) }}
        </div>
    </div>

    <div class="body-content">
        @foreach($resume->sections->sortBy('sort_order') as $section)
            <div class="section">
                <h2>{{ $section->title }}</h2>
                @if($section->selectedVariant)
                    <div class="section-content">
                        {!! Str::markdown($section->selectedVariant->content) !!}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</body>
</html>
