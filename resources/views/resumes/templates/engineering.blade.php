<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $header['name'] ?? $user->name ?? 'Resume' }}</title>
    <style>
        @page {
            size: letter;
            margin: 0.5in 0.6in;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', 'Georgia', serif;
            font-size: 10pt;
            line-height: 1.25;
            color: #1a1a1a;
            padding: 0;
        }
        .contact-header {
            text-align: center;
            border-bottom: 1px solid #999;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }
        .contact-header h1 {
            font-size: 16pt;
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
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 10pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-bottom: 1px solid #999;
            padding-bottom: 1px;
            margin-bottom: 4px;
            page-break-after: avoid;
        }
        .section-content {
            font-size: 10pt;
            line-height: 1.25;
        }
        .section-content p { margin-bottom: 2px; }
        .section-content ul { margin: 1px 0 4px 16px; }
        .section-content li { margin-bottom: 1px; }
        .section-content strong { font-weight: 700; }
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
                    @php
                        $variant = $section->selectedVariant;
                        $sectionContent = ($section->display_mode === 'compact' && $variant->compact_content)
                            ? $variant->compact_content
                            : $variant->content;
                    @endphp
                    <div class="section-content">
                        {!! Str::markdown(str_replace(['\\n', '\\r'], ["\n", "\r"], $sectionContent)) !!}
                    </div>
                @endif
            </div>
        @endif
    @endforeach

    @if($resume->show_transparency && $resume->transparency_text)
        <div style="margin-top: 20px; text-align: center; font-size: 8pt; color: #999; font-style: italic;">
            {{ $resume->transparency_text }}
        </div>
    @endif
</body>
</html>
