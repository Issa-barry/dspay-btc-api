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
      <h1 style="margin:0;font-size:22px;line-height:1.25;color:{{ $text }};">Valider la création de votre compte</h1>
    </td></tr>
    <!-- intro -->
    <tr><td style="padding:8px 28px 0 28px;color:{{ $muted }};">
      @if(!empty($userName)) Bonjour {{ $userName }} 👋,@else Bonjour 👋,@endif
    </td></tr>
    <tr><td style="padding:6px 28px 0 28px;color:{{ $muted }};">
      Cliquez sur le bouton ci-dessous pour confirmer votre adresse e-mail et activer votre compte.
    </td></tr>
    <!-- CTA (bulletproof) -->
    <tr><td align="left" style="padding:18px 28px 6px 28px;">
      <!--[if mso]>
      <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $url }}" arcsize="20%" strokecolor="{{ $primary }}" fillcolor="{{ $primary }}" style="height:44px;v-text-anchor:middle;width:240px;">
        <w:anchorlock/><center style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:700;">Valider mon compte</center>
      </v:roundrect>
      <![endif]-->
      <!--[if !mso]><!-- -->
      <a href="{{ $url }}" style="display:inline-block;background:{{ $primary }};color:#fff;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:600;">Valider mon compte</a>
      <!--<![endif]-->
    </td></tr>
    <!-- notes -->
    <tr><td style="padding:8px 28px 0 28px;font-size:13px;color:{{ $muted }};">Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet e-mail.</td></tr>
    <tr><td style="padding:8px 28px 24px 28px;font-size:13px;color:{{ $muted }};">
      Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
      <a href="{{ $url }}" style="color:{{ $primary }};text-decoration:none;word-break:break-all;">{{ $url }}</a>
    </td></tr>
    <!-- footer -->
    <tr><td style="border-top:1px solid #EEF2FF;padding:16px 28px 24px 28px;color:#94A3B8;font-size:12px;">
      © {{ date('Y') }} {{ $appName }} — Tous droits réservés.
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>
