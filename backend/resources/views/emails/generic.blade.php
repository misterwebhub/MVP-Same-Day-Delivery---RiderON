{{--
    Default view for EmailService::send()/queue() when a caller doesn't yet
    have its own dedicated Blade view. Expects:
      - $heading (string, optional)
      - $lines   (array<int, string>, optional) — rendered as paragraphs
      - $ctaUrl / $ctaLabel (string, optional) — rendered as a button

    For anything more specific, add a new file under resources/views/emails/
    (it can still <x-emails.layout> the same wrapper) and pass its dotted
    name as the $view argument instead of "emails.generic".
--}}
<x-emails.layout>
    @if (! empty($heading))
        <h2 style="margin:0 0 16px;font-size:18px;">{{ $heading }}</h2>
    @endif

    @foreach ($lines ?? [] as $line)
        <p style="margin:0 0 12px;">{{ $line }}</p>
    @endforeach

    @if (! empty($ctaUrl) && ! empty($ctaLabel))
        <p style="margin:24px 0 0;">
            <a href="{{ $ctaUrl }}" style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:6px;font-size:14px;">
                {{ $ctaLabel }}
            </a>
        </p>
    @endif
</x-emails.layout>
