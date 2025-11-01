@component('mail::message')
# Confirmation de dépôt

Bonjour,

Votre dépôt a été effectué avec succès.

**Service :** {{ $depot->serviceId }}  
**Montant :** {{ number_format($depot->amount, 2, ',', ' ') }} GNF  
**Référence :** {{ $depot->transaction_ref }}  
**Statut :** {{ ucfirst($depot->status) }}

Merci d’avoir utilisé notre service.

Cordialement,  
L’équipe DSPAY
@endcomponent
