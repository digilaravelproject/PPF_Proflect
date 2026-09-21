@props(['name'])
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @if($name === 'view')
        <path d="M2.5 12s3.5-5.5 9.5-5.5 9.5 5.5 9.5 5.5-3.5 5.5-9.5 5.5S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.6"/>
    @elseif($name === 'edit')
        <path d="m4 20 4.5-1 10.7-10.7a2.1 2.1 0 0 0-3-3L5.5 16Z"/><path d="m14.6 6.9 2.5 2.5M4 20h16"/>
    @elseif($name === 'delete')
        <path d="M4.5 7h15M9 7V4.5h6V7M6.5 7l.8 13h9.4l.8-13M10 10.5v6M14 10.5v6"/>
    @endif
</svg>
