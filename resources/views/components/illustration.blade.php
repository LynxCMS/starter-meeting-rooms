@props(['name' => null, 'label' => null, 'eager' => false])
{{--
    One of the site's illustrations (SiteSchemaSeeder::ILLUSTRATIONS), picked by an illustration field:
    a scene in public/images/illustrations, served as a file so browsers cache it across pages.
    Unknown or empty names show the lake. Decorative (empty alt) unless given a label.
--}}
@php($name = array_key_exists((string) $name, \Database\Seeders\SiteSchemaSeeder::ILLUSTRATIONS) ? (string) $name : 'lake')
<img src="{{ asset("images/illustrations/{$name}.svg") }}" alt="{{ $label ?? '' }}" width="2304" height="1792"
     @unless ($eager) loading="lazy" @endunless decoding="async" data-illustration="{{ $name }}"
     {{ $attributes->merge(['class' => 'block h-auto']) }}>
