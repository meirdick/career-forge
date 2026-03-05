@php
    $bladeTemplate = match($template ?? 'classic') {
        'moderncv', 'engineeringresumes', 'engineeringclassic' => 'resumes.templates.modern',
        default => 'resumes.templates.classic',
    };
@endphp

@include($bladeTemplate, ['resume' => $resume, 'user' => $user])
