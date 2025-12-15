@php
  $primary = '#11BA82'; $text='#0F172A'; $muted='#64748B'; $bg='#F6F7FB';
@endphp
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
<title>Vérification e-mail – {{ $appName }}</title>
</head>
<body style="margin:0;background:{{ $bg }};font-family:Inter,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:{{ $text }};line-height:1.6;">
<!-- préheader -->
<div style="display:none;max-height:0;overflow:hidden;font-size:1px;line-height:1px;color:#fff;opacity:0;">
  Confirmez votre adresse e-mail pour activer votre compte {{ $appName }}.
</div>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:{{ $bg }};">
<tr><td align="center" style="padding:32px 16px;">
  <table role="presentation" width="640" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#fff;border:1px solid #EEF2FF;border-radius:16px;box-shadow:0 8px 30px rgba(15,23,42,.06);overflow:hidden;">
    <!-- top bar -->
    <tr><td style="height:6px;background:{{ $primary }};line-height:6px;font-size:0;">&nbsp;</td></tr>
    <!-- header -->
    <tr><td style="padding:24px 28px 0 28px;font-weight:700;letter-spacing:.2px;">{{ $appName }}</td></tr>
    <!-- title -->
    <tr><td style="padding:8px 28px 0 28px;">
      <h1 style="margin:0;font-size:22px;line-height:1.25;color:{{ $text }};">Code de vérification</h1>
    </td></tr>
    <!-- intro -->
    <tr><td style="padding:8px 28px 0 28px;color:{{ $muted }};">
      @if(!empty($userName)) Bonjour {{ $userName }} 👋,@else Bonjour 👋,@endif
    </td></tr>
    <tr><td style="padding:6px 28px 0 28px;color:{{ $muted }};">
      Veuillez utiliser le code ci-dessous pour confirmer votre adresse e-mail et activer votre compte.
    </td></tr>
    <!-- CODE (grand et visible) -->
    <tr><td align="center" style="padding:24px 28px;">
      <div style="background:#F1F5F9;border:2px dashed {{ $primary }};border-radius:12px;padding:20px;display:inline-block;">
        <div style="font-size:32px;font-weight:700;letter-spacing:8px;color:{{ $text }};font-family:monospace;">{{ $code }}</div>
      </div>
    </td></tr>
    <!-- Expiration -->
    <tr><td style="padding:0px 28px 0 28px;font-size:13px;color:{{ $muted }};text-align:center;">
      Ce code expire dans {{ $expiresIn }} minutes
    </td></tr>
    <!-- notes -->
    <tr><td style="padding:16px 28px 0 28px;font-size:13px;color:{{ $muted }};">Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</td></tr>
    <tr><td style="padding:8px 28px 24px 28px;font-size:13px;color:{{ $muted }};">
      Pour des raisons de sécurité, ne partagez jamais ce code avec qui que ce soit.
    </td></tr>
    <!-- footer -->
    <tr><td style="border-top:1px solid #EEF2FF;padding:16px 28px 24px 28px;color:#94A3B8;font-size:12px;">
      © {{ date('Y') }} {{ $appName }} — Tous droits réservés.
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>
