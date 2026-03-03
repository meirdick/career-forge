<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $resume->title }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 0;
            padding: 40px;
        }
        h1 {
            font-size: 22pt;
            margin-bottom: 4px;
            color: #111;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 13pt;
            color: #222;
            margin-top: 18px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }
        .section-content {
            margin-bottom: 14px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>{{ $resume->title }}</h1>
    @if($resume->jobPosting)
        <div class="subtitle">
            {{ $resume->jobPosting->title ?? 'Untitled' }} at {{ $resume->jobPosting->company ?? 'Unknown' }}
        </div>
    @endif

    @foreach($resume->sections->sortBy('sort_order') as $section)
        <h2>{{ $section->title }}</h2>
        @if($section->selectedVariant)
            <div class="section-content">{{ $section->selectedVariant->content }}</div>
        @endif
    @endforeach
</body>
</html>
