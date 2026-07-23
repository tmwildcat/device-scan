<footer style="margin-top:4rem;border-top:1px solid #334155;background:#0f172a;color:#cbd5e1;padding:2rem 1rem">
    <div style="max-width:70rem;margin:auto;display:flex;flex-wrap:wrap;justify-content:space-between;gap:2rem">
        <div><strong style="color:white">LineWatt Library</strong><p>Governed public legal information.</p></div>
        @if($footerDocuments->isNotEmpty())
            <nav aria-label="Legal"><strong style="color:white">Legal</strong><ul>@foreach($footerDocuments as $item)<li><a style="color:#a7f3d0" href="{{ $item['href'] }}">{{ $item['title'] }}</a></li>@endforeach</ul></nav>
        @endif
    </div>
    <p style="text-align:center;color:#64748b">© {{ now()->year }} LineWatt. All rights reserved.</p>
</footer>
