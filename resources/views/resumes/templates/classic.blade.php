<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $user->name ?? 'Resume' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 10.5pt;
            line-height: 1.4;
            color: #1a1a1a;
            padding: 36px 48px;
        }
        .contact-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .contact-header h1 {
            font-size: 22pt;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .contact-info {
            font-size: 9pt;
            color: #555;
            line-height: 1.6;
        }
        .contact-info a {
            color: #555;
            text-decoration: none;
        }
        .section {
            margin-top: 14px;
        }
        .section h2 {
            font-size: 11pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1.5px solid #333;
            padding-bottom: 2px;
            margin-bottom: 6px;
        }
        .section-content {
            font-size: 10.5pt;
            line-height: 1.45;
        }
        .section-content p { margin-bottom: 4px; }
        .section-content ul { margin: 2px 0 6px 18px; }
        .section-content li { margin-bottom: 2px; }
        .section-content strong { font-weight: 600; }
    </style>
</head>
<body>
    <div class="contact-header">
        <h1>{{ $user->name ?? 'Candidate' }}</h1>
        <div class="contact-info">
            @php
                $parts = array_filter([
                    $user->email,
                    $user->phone,
                    $user->location,
                    $user->linkedin_url,
                    $user->portfolio_url,
                ]);
            @endphp
            {{ implode(' | ', $parts) }}
        </div>
    </div>

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
</body>
</html>
