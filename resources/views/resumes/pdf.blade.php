@php
    $bladeTemplate = match($template ?? 'classic') {
        'moderncv' => 'resumes.templates.modern',
        'sb2nov' => 'resumes.templates.sb2nov',
        'engineeringresumes' => 'resumes.templates.engineering',
        'engineeringclassic' => 'resumes.templates.engineering-classic',
        default => 'resumes.templates.classic',
    };
@endphp

@include($bladeTemplate, ['resume' => $resume, 'user' => $user, 'header' => $header])
