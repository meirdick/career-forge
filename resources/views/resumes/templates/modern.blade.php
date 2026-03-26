@php
    $fsa = $fontSizeAdjust ?? 0;
    $spa = $spacingAdjust ?? 0;
    $mga = $marginAdjust ?? 0;
    $overrides = $sectionOverrides ?? [];
    $hidden = $hiddenSections ?? [];

    $bodyFontSize = 10.5 + $fsa;
    $contentFontSize = 10.5 + $fsa;
    $sectionHeadingSize = 12 + $fsa;
    $pageMarginV = 0.5 + $mga;
    $pageMarginH = 0.6 + $mga;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $header['name'] ?? $user->name ?? 'Resume' }}</title>
    <style>
        @page {
            size: letter;
            margin: {{ $pageMarginV }}in {{ $pageMarginH }}in;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Calibri', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: {{ $bodyFontSize }}pt;
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
            padding: {{ max(10, 20 + $spa) }}px 48px 36px;
        }
        .section {
            margin-bottom: {{ max(6, 16 + $spa) }}px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: {{ $sectionHeadingSize }}pt;
            font-weight: 700;
            color: #1e3a5f;
            border-left: 3px solid #1e3a5f;
            padding-left: 10px;
            margin-bottom: {{ max(4, 8 + $spa) }}px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            page-break-after: avoid;
        }
        .section-content {
            font-size: {{ $contentFontSize }}pt;
            line-height: {{ 1.45 + ($spa * 0.02) }};
            padding-left: 13px;
        }
        .section-content p { margin-bottom: {{ max(1, 4 + $spa) }}px; }
        .section-content ul { margin: {{ max(0, 2 + $spa) }}px 0 {{ max(2, 6 + $spa) }}px 18px; }
        .section-content li { margin-bottom: {{ max(0, 2 + $spa) }}px; }
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
            @if(!$section->is_hidden && !in_array($section->id, $hidden) && $section->selectedVariant && trim($section->selectedVariant->content) !== '')
                @php
                    $variant = $section->selectedVariant;
                    $cOverrides = $contentOverrides ?? [];
                    if (isset($cOverrides[$section->id])) {
                        $sectionContent = $cOverrides[$section->id];
                    } elseif (($section->display_mode === 'compact' || isset($overrides[$section->id])) && $variant->compact_content) {
                        $sectionContent = $variant->compact_content;
                    } else {
                        $sectionContent = $variant->content;
                    }
                @endphp
                <div class="section" data-section-id="{{ $section->id }}" data-section-type="{{ $section->type?->value }}">
                    <h2>{{ $section->title }}</h2>
                    <div class="section-content">
                        {!! Str::markdown(str_replace(['\\n', '\\r'], ["\n", "\r"], $sectionContent)) !!}
                    </div>
                </div>
            @endif
        @endforeach

        @if($resume->show_transparency && $resume->transparency_text)
            <div style="margin-top: 20px; text-align: center; font-size: 8pt; color: #999; font-style: italic;">
                {{ $resume->transparency_text }}
            </div>
        @endif
    </div>
</body>
</html>
