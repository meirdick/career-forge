<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $header['name'] ?? $user->name ?? 'Resume' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            color: #1a1a1a;
            padding: 28px 40px;
        }
        .contact-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .contact-header h1 {
            font-size: 18pt;
            font-weight: 700;
            color: #111;
            margin-bottom: 2px;
        }
        .contact-info {
            font-size: 9pt;
            color: #555;
            line-height: 1.4;
        }
        .contact-info a {
            color: #555;
            text-decoration: none;
        }
        .section {
            margin-top: 10px;
        }
        .section h2 {
            font-size: 10pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 2.5px solid #111;
            padding-bottom: 1px;
            margin-bottom: 4px;
        }
        .section-content {
            font-size: 10pt;
            line-height: 1.3;
        }
        .section-content p { margin-bottom: 2px; }
        .section-content ul { margin: 1px 0 4px 16px; }
        .section-content li { margin-bottom: 1px; }
        .section-content strong { font-weight: 600; }
    </style>
</head>
<body>
    <div class="contact-header">
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

    @foreach($resume->sections->sortBy('sort_order') as $section)
        @if(!$section->is_hidden)
            <div class="section">
                <h2>{{ $section->title }}</h2>
                @if($section->selectedVariant)
                    <div class="section-content">
                        {!! Str::markdown($section->selectedVariant->content) !!}
                    </div>
                @endif
            </div>
        @endif
    @endforeach
</body>
</html>
