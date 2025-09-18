@php
    $primary = '#5B61E6'; /* violet doux comme ta maquette */
    $text    = '#0F172A';
    $muted   = '#64748B';
    $bg      = '#F6F7FB';
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vérification e-mail – {{ $appName }}</title>
<style>
/* mobile-first reset */
*{box-sizing:border-box} body{margin:0;background:{{ $bg }};font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:{{ $text }};}
.container{max-width:640px;margin:32px auto;padding:0 16px}
.card{background:#fff;border-radius:16px;box-shadow:0 8px 30px rgba(15,23,42,.06);overflow:hidden}
.header{padding:28px 28px 0}
.brand{display:flex;align-items:center;gap:10px}
.brand__logo{width:28px;height:28px;border-radius:8px;background:{{ $primary }};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px}
.brand__name{font-weight:700;letter-spacing:.2px}
.content{padding:4px 28px 28px}
h1{margin:12px 0 8px;font-size:22px;line-height:1.2}
p{margin:10px 0;color:{{ $muted }};line-height:1.6}
.cta{display:inline-block;margin:18px 0 6px;padding:12px 18px;background:{{ $primary }};color:#fff !important;border-radius:12px;text-decoration:none;font-weight:600}
.note{font-size:13px;color:{{ $muted }};margin-top:10px}
.footer{padding:16px 28px 24px;border-top:1px solid #EEF2FF;color:#94A3B8;font-size:12px}
.link{word-break:break-all;color:{{ $primary }};text-decoration:none}
</style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="header">
        <div class="brand">
          {{-- Logo minimal (remplace par <img src="https://.../logo.png" width="28" height="28"> si tu as un logo) --}}
          {{--  <div class="brand__logo">{{ $appName }}</div>  --}}
          <div class="brand__name">{{ $appName }}</div>
        </div>
      </div>

      <div class="content">
        <h1>Valider la création de votre compte</h1>
        <p>
          @if(!empty($userName))
            Bonjour {{ $userName }} 👋,
          @else
            Bonjour 👋,
          @endif
        </p>
        <p>Cliquez sur le bouton ci-dessous pour confirmer votre adresse e-mail et activer votre compte.</p>

        <p>
          <a href="{{ $url }}" class="cta">Valider mon compte</a>
        </p>

        <p class="note">Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail.</p>

        <p class="note">
          Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
          <a class="link" href="{{ $url }}">{{ $url }}</a>
        </p>
      </div>

      <div class="footer">
        © {{ date('Y') }} {{ $appName }} — Tous droits réservés.
      </div>
    </div>
  </div>
</body>
</html>
