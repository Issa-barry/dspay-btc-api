<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Détails de votre transfert</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;color:#1f2937;font-family:Arial,Helvetica,sans-serif;line-height:1.5;">
  <!-- Wrapper -->
  <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f6f7fb;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <!-- Container -->
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="620" style="max-width:620px;background:transparent;">
          <!-- Brand -->
          <tr>
            <td align="center" style="font-weight:900;font-size:24px;letter-spacing:.2px;color:#16A34A;padding:8px 0 16px 0;">
              DSPAY
            </td>
          </tr>

          <!-- Title -->
          <tr>
            <td align="center" style="font-size:24px;line-height:1.25;color:#1f2937;padding:0 12px 8px 12px;">
              Détails du transfert
            </td>
          </tr>

          <!-- Lead -->
          <tr>
            <td align="center" style="padding:0 12px 16px 12px;color:#6b7280;">
              Bonjour
              {{ optional($transfert->user)->nom_complet
                  ?? optional($transfert->user)->name
                  ?? optional($transfert->user)->email
                  ?? '' }} ,
              voici le récapitulatif de votre opération.
            </td>
          </tr>

          @php
            $devSrc = optional($transfert->deviseSource)->tag ?? 'EUR';
            $devDst = optional($transfert->deviseCible)->tag ?? 'GNF';
            $benef  = optional($transfert->beneficiaire);
            $benefNomComplet = trim(($benef->prenom ?? '').' '.($benef->nom ?? '')) ?: ($benef->nom_complet ?? '—');
          @endphp

          <!-- Ticket card -->
          <tr>
            <td style="padding:0 0 6px 0;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;">
                <!-- Header -->
                <tr>
                  <td colspan="2" style="background:#16A34A;color:#ffffff;font-weight:700;font-size:16px;padding:16px 20px;border-radius:16px 16px 0 0;">
                    Reçu de transfert
                  </td>
                </tr>

                <!-- Total débité -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Total débité</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;font-weight:700;font-size:18px;color:#1f2937;">
                    {{ number_format((float)$transfert->total_ttc, 2, ',', ' ') }} {{ $devSrc }}
                  </td>
                </tr>

                <!--  Frais -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Frais</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;font-weight:700;font-size:18px;color:#1f2937;">
                    {{ number_format((float)$transfert->frais, 2, ',', ' ') }} {{ $devSrc }}
                  </td>
                </tr>

                <!-- Taux -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Taux</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;font-weight:700;font-size:18px;color:#1f2937;">
                    {{--  1 {{ $devSrc }} = {{ $transfert->taux_applique }} {{ $devDst }}  --}}
                     1 {{ $devSrc }} = {{ number_format((float)$transfert->taux_applique, 0, ',', ' ') }} {{ $devDst }}
                  </td>
                </tr>

                <!-- Bénéficiaire -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Bénéficiaire</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#1f2937;">
                    {{ $benefNomComplet }}<br>
                    {{ $benef->phone ?? '—' }}
                  </td>
                </tr>

                <!-- Montant à récupérer -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Montant à récupérer</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;font-weight:700;font-size:18px;color:#1f2937;">
                    {{ number_format((float)$transfert->montant_gnf, 0, ',', ' ') }} {{ $devDst }}
                  </td>
                </tr>

                <!-- Code de retrait -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Code de retrait</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#0E7A3E;">
                 <span style="display:inline-block;background:#ECFDF5;border:1px dashed #16A34A;color:#0E7A3E;padding:6px 14px;border-radius:10px;font-weight:700;letter-spacing:.5px;white-space:nowrap;">
                {{ $transfert->code }}
              </span>

                  </td>
                </tr>

                <!-- Date 'd\'envoi' -->
                <tr>
                  <td width="220" style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#6b7280;">Date d’envoi</td>
                  <td style="padding:12px 20px;border-top:1px dashed #e5e7eb;color:#1f2937;">
                    {{ optional($transfert->created_at)->format('d/m/Y H:i') }}
                  </td>
                </tr>

                <!-- CTA -->
                <tr>
                  <td colspan="2" align="center" style="padding:20px 20px 22px 20px;border-top:1px dashed #e5e7eb;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                      <tr>
                        <td bgcolor="#16A34A" style="border-radius:999px;">
                          <a href="#"
                             style="display:inline-block;padding:12px 22px;border-radius:999px;background:#16A34A;color:#ffffff;font-weight:700;text-decoration:none;">
                            Suivre mon transfert
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="font-size:12px;color:#6b7280;padding:18px 12px 0 12px;">
              Besoin d’aide ? +33 7 58 85 50 39 ·
              <a href="mailto:contact@dspay.com" style="color:#16A34A;text-decoration:none;">contact@dspay.com</a>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
