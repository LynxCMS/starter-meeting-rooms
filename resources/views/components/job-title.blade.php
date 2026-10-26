@props(['lawyer'])
{{--
    What a lawyer is called: their own title in this language when they have one, else their role.
    Compared as a string: a title written only in French reads in English as an empty value, not null.
--}}
{{ (string) $lawyer->job_title !== '' ? $lawyer->job_title : $lawyer->role }}
