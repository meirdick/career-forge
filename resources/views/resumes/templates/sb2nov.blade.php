@php
    $fsa = $fontSizeAdjust ?? 0;
    $spa = $spacingAdjust ?? 0;
    $mga = $marginAdjust ?? 0;
    $overrides = $sectionOverrides ?? [];
    $hidden = $hiddenSections ?? [];

    $bodyFontSize = 10 + $fsa;
    $contentFontSize = 10 + $fsa;
    $sectionHeadingSize = 10 + $fsa;
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
            line-height: 1.3;
            color: #1a1a1a;
            padding: 0;
        }
        .contact-header {
            text-align: center;
            margin-bottom: {{ max(4, 10 + $spa) }}px;
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
            margin-top: {{ max(4, 10 + $spa) }}px;
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: {{ $sectionHeadingSize }}pt;
            font-weight: 700;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 2.5px solid #111;
            padding-bottom: 1px;
            margin-bottom: {{ max(1, 4 + $spa) }}px;
            page-break-after: avoid;
        }
        .section-content {
            font-size: {{ $contentFontSize }}pt;
            line-height: {{ 1.3 + ($spa * 0.02) }};
        }
        .section-content p { margin-bottom: {{ max(0, 2 + $spa) }}px; }
        .section-content ul { margin: {{ max(0, 1 + $spa) }}px 0 {{ max(1, 4 + $spa) }}px 16px; }
        .section-content li { margin-bottom: {{ max(0, 1 + $spa) }}px; }
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
</body>
</html>
