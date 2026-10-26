@props(['lawyer'])
{{-- What a lawyer is called: their own title in this language when they have one, else their role. --}}
{{ $lawyer->job_title ?: $lawyer->role }}
