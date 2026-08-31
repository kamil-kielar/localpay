@extends('layouts.app')
@section('title', 'LokalPay Pro — najem i płatności pod kontrolą')
@section('description', 'Portfel nieruchomości, czynsze, najemcy, płatności Stripe i PayU oraz analityka ROI.')
@section('content')
<nav class="navbar navbar-expand-lg fixed-top lp-nav" aria-label="Nawigacja główna">
    <div class="container">
        <a class="navbar-brand fw-bold text-white" href="#"><span class="brand-mark">L</span> LokalPay <span class="text-lime">Pro</span></a>
        <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Otwórz menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                <li><a class="nav-link" href="#korzysci">Korzyści</a></li><li><a class="nav-link" href="#bezpieczenstwo">Bezpieczeństwo</a></li>
                <li><a class="nav-link" href="#cennik">Cennik</a></li><li><a class="nav-link" href="#faq">FAQ</a></li>
                <li><a class="btn btn-outline-light" href="{{ route('login') }}">Zaloguj się</a></li>
                <li><a class="btn btn-lime" href="{{ route('register') }}">Wypróbuj bezpłatnie</a></li>
            </ul>
        </div>
    </div>
</nav>
<main>
    <section class="hero">
        <div class="hero-orb hero-orb-one"></div><div class="hero-orb hero-orb-two"></div>
        <div class="container position-relative">
            <div class="row align-items-center g-5 min-vh-100 py-5">
                <div class="col-lg-6 pt-5">
                    <span class="eyebrow">FINANSE NAJMU W JEDNYM MIEJSCU</span>
                    <h1 class="display-3 fw-bold mt-3">Twój portfel.<br><span class="gradient-text">Pełna kontrola.</span></h1>
                    <p class="lead text-white-50 my-4">Czynsze, umowy, najemcy, płatności i ROI — bez arkuszy, chaosu i ręcznego pilnowania terminów.</p>
                    <div class="d-flex flex-wrap gap-3"><a href="{{ route('register') }}" class="btn btn-lime btn-lg">Zacznij za 0 PLN</a><a href="#podglad" class="btn btn-outline-light btn-lg">Zobacz panel</a></div>
                    <div class="d-flex gap-4 mt-4 small text-white-50"><span>✓ Bez karty</span><span>✓ 3 nieruchomości gratis</span><span>✓ Dane w UE</span></div>
                </div>
                <div class="col-lg-6" id="podglad">
                    <div class="glass-dashboard">
                        <div class="preview-sidebar"><b>LP</b><span></span><span></span><span></span><span></span></div>
                        <div class="preview-main">
                            <div class="d-flex justify-content-between"><div><small>PORTFEL</small><h5>Dzień dobry, Anna</h5></div><span class="preview-avatar">AK</span></div>
                            <div class="row g-2 my-3"><div class="col-4"><div class="preview-kpi"><small>Przychód</small><b>18 450 zł</b><em>+12,4%</em></div></div><div class="col-4"><div class="preview-kpi"><small>ROI</small><b>8,7%</b><em>+0,8 pp</em></div></div><div class="col-4"><div class="preview-kpi"><small>Zaległości</small><b>1 200 zł</b><i>2 płatności</i></div></div></div>
                            <div class="preview-chart"><span style="height:28%"></span><span style="height:42%"></span><span style="height:38%"></span><span style="height:63%"></span><span style="height:55%"></span><span style="height:82%"></span><span style="height:75%"></span><span style="height:94%"></span></div>
                            <div class="preview-list"><span><b>Studio Mokotów</b><em>OPŁACONE</em></span><span><b>Apartament Centrum</b><i>DO 10 WRZ</i></span><span><b>Lokal Gdańsk</b><strong>ZALEGŁE</strong></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="korzysci" class="section-pad bg-soft"><div class="container">
        <div class="section-heading"><span class="eyebrow dark">MNIEJ RĘCZNEJ PRACY</span><h2>Wszystko, czego potrzebuje wynajmujący</h2></div>
        <div class="row g-4">
            @foreach([['bi-buildings','Portfel nieruchomości','Koszt zakupu, miesięczne wpływy, odzyskany kapitał i historia bez limitu lat.'],['bi-receipt','Czynsze i należności','Automatyczny harmonogram, statusy należne, częściowe, zaległe, opłacone i nieważne.'],['bi-people','Najemcy i umowy','Bezpieczne zaproszenia e-mail, portal najemcy i szybki podpisany dostęp.'],['bi-graph-up-arrow','ROI i prognozy','Porównuj lata i przewiduj termin odzyskania kapitału.'],['bi-credit-card','Płatności online','Stripe lub PayU. Kwota zawsze pochodzi z należności w systemie.'],['bi-bell','Powiadomienia','Przypomnienia e-mail i w aplikacji, potwierdzenia oraz kolejka zadań.']] as $item)
            <div class="col-md-6 col-lg-4"><article class="feature-card"><i class="bi {{ $item[0] }}"></i><h3>{{ $item[1] }}</h3><p>{{ $item[2] }}</p></article></div>
            @endforeach
        </div>
    </div></section>
    <section class="section-pad"><div class="container"><div class="row align-items-center g-5">
        <div class="col-lg-6"><span class="eyebrow dark">PORTAL NAJEMCY</span><h2 class="display-6 fw-bold">Prostsze rozliczenia dla obu stron</h2><p class="text-secondary fs-5">Najemca widzi umowę, terminy, zaległości, historię wpłat i powiadomienia. Może zapłacić online bez wpisywania kwoty.</p><ul class="check-list"><li>Kwota pobierana bezpośrednio z należności</li><li>Jeden aktywny checkout na należność</li><li>Potwierdzenia po zweryfikowanym webhooku</li><li>Telefon wyłącznie jako dana kontaktowa — bez deklaracji SMS</li></ul></div>
        <div class="col-lg-6"><div class="tenant-preview"><span class="badge text-bg-success">Portal najemcy</span><h4>Apartament Centrum</h4><p>Najbliższa płatność</p><div class="tenant-amount">3 200,00 zł</div><small>Termin: 10 września 2026</small><button class="btn btn-lime w-100 mt-4" disabled>Zapłać bezpiecznie online</button></div></div>
    </div></div></section>
    <section id="bezpieczenstwo" class="section-pad security-section"><div class="container text-center"><span class="eyebrow">BEZPIECZEŃSTWO</span><h2 class="text-white my-3">Twoje dane i pieniądze mają właściwe granice</h2><p class="text-white-50 mx-auto mb-5 max-copy">Izolacja organizacji, role i polityki dostępu, szyfrowane sesje, CSRF, podpisane linki, audyt i weryfikowane webhooki.</p><div class="row g-3"><div class="col-md-3"><div class="security-chip">🔒 Bezpieczne sesje</div></div><div class="col-md-3"><div class="security-chip">🏢 Izolacja tenantów</div></div><div class="col-md-3"><div class="security-chip">🧾 Dziennik audytu</div></div><div class="col-md-3"><div class="security-chip">✓ Webhook verification</div></div></div></div></section>
    <section id="cennik" class="section-pad bg-soft"><div class="container"><div class="section-heading"><span class="eyebrow dark">PROSTE CENY</span><h2>Plan dopasowany do Twojego portfela</h2></div><div class="row g-4 justify-content-center">
        @foreach([['Free','0','3 nieruchomości',['Portfel i miesięczne wpływy','Najemcy i umowy','Należności i płatności offline']],['Growth','49','10 nieruchomości',['Wszystko z Free','Prognozy zwrotu','Porównania rok do roku']],['Pro','129','50 nieruchomości',['Wszystko z Growth','Zaawansowana analityka','Wsparcie priorytetowe']]] as $i => $plan)
        <div class="col-md-4"><article class="price-card {{ $i === 1 ? 'featured' : '' }}">@if($i===1)<span class="popular">NAJPOPULARNIEJSZY</span>@endif<h3>{{ $plan[0] }}</h3><div class="price">{{ $plan[1] }} <small>PLN / mies.</small></div><b>Do {{ $plan[2] }}</b><ul>@foreach($plan[3] as $feature)<li>✓ {{ $feature }}</li>@endforeach</ul><a href="{{ route('register', ['plan' => strtolower($plan[0])]) }}" class="btn {{ $i===1 ? 'btn-lime' : 'btn-dark' }} w-100">Wybieram {{ $plan[0] }}</a>@if($i>0)<small class="d-block mt-3 text-secondary">PayU: jednorazowy dostęp 30 dni, bez odnowienia.</small>@endif</article></div>
        @endforeach
    </div></div></section>
    <section id="faq" class="section-pad"><div class="container max-copy"><div class="section-heading"><h2>Najczęstsze pytania</h2></div><div class="accordion" id="faqList">
        @foreach([['Czy mogę zacząć bez karty?','Tak. Plan Free pozwala obsłużyć do 3 nieruchomości bez podawania karty.'],['Czy PayU odnawia plan automatycznie?','Nie. Zakup planu przez PayU daje jednorazowo 30 dni dostępu.'],['Czy system wysyła SMS?','Nie. Telefon jest metadanym kontaktowym. Zaproszenia i przypomnienia korzystają z e-maila i powiadomień w aplikacji.'],['Jak chronione są płatności?','Serwer wybiera kwotę z należności, a płatność uznaje dopiero po sprawdzeniu podpisu, kwoty, waluty i lokalnego zamówienia.']] as $i => $faq)
        <div class="accordion-item"><h3 class="accordion-header"><button class="accordion-button {{ $i ? 'collapsed' : '' }}" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">{{ $faq[0] }}</button></h3><div id="faq{{ $i }}" class="accordion-collapse collapse {{ !$i ? 'show' : '' }}" data-bs-parent="#faqList"><div class="accordion-body">{{ $faq[1] }}</div></div></div>
        @endforeach
    </div></div></section>
</main>
<footer class="lp-footer"><div class="container d-flex flex-wrap justify-content-between gap-3"><b><span class="brand-mark">L</span> LokalPay Pro</b><span>© {{ date('Y') }} LokalPay. Bezpieczne zarządzanie najmem.</span><div><a href="#bezpieczenstwo">Bezpieczeństwo</a> · <a href="#faq">FAQ</a></div></div></footer>
@endsection
