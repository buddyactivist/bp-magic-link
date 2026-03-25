# BP Magic Link

Un plugin per WordPress e BuddyPress che semplifica l'onboarding degli utenti introducendo un'autenticazione *passwordless*. Permette la registrazione e l'accesso al sito utilizzando esclusivamente un indirizzo email e un "Magic Link", eliminando del tutto la necessità per gli utenti di gestire e ricordare le password.

## 🚀 Caratteristiche Principali

* **Accesso e Registrazione Unificati:** Un unico form (tramite shortcode) gestisce sia i nuovi utenti che quelli esistenti.
* **Flusso BuddyPress Integrato:** I nuovi utenti vengono generati automaticamente con credenziali sicure casuali e reindirizzati direttamente alla pagina di modifica profilo di BuddyPress per completare i propri dati.
* **Pulizia Automatica (Cron Job):** Gli account appena creati che non completano il profilo BuddyPress entro 1 ora vengono eliminati automaticamente dal database per evitare utenti fantasma o spam.
* **Sicurezza Admin con PIN Segreto:** Il classico `wp-login.php` è protetto da un PIN segreto personalizzabile. Solo gli amministratori in possesso del PIN possono accedere con la classica accoppiata Username + Password in caso di emergenza.
* **Blocco Registrazioni Standard:** Le pagine di registrazione native di WordPress (`wp-signup.php`) e di BuddyPress vengono bloccate e reindirizzate alla home page per forzare l'uso del Magic Link.
* **Pronto per le Traduzioni (i18n):** Include il file `.pot` per tradurre facilmente le stringhe del plugin in qualsiasi lingua tramite Loco Translate o Poedit.

## 📋 Requisiti

* WordPress 5.0 o superiore
* BuddyPress attivo (consigliato per il flusso di reindirizzamento completo)
* Un server SMTP configurato correttamente per garantire il recapito delle email con il Magic Link.

## 🛠️ Installazione

1. Scarica i file del plugin e inseriscili in una cartella chiamata `bp-magic-link`.
2. Carica la cartella all'interno della directory `/wp-content/plugins/` della tua installazione WordPress, oppure comprimila in formato `.zip` e caricala da **Plugin > Aggiungi nuovo > Carica plugin**.
3. Vai nella bacheca di WordPress alla voce **Plugin** e attiva "BP Magic Link".

## ⚙️ Configurazione (IMPORTANTE)

Prima di attivare il plugin in un ambiente di produzione, **devi personalizzare il tuo PIN di sicurezza per gli amministratori**.

1. Apri il file `bp-magic-link.php` con un editor di testo.
2. Scorri fino alla sezione `6B. Verifica il codice segreto durante il tentativo di login`.
3. Trova la seguente riga di codice:
   ```php
   $mio_pin_segreto = '987654';
   
4. Sostituisci 987654 con una parola d'ordine o un PIN numerico a tua scelta.
5. Salva il file. Quando avrai bisogno di accedere da wp-login.php, ti verrà richiesto di inserire questo PIN sotto alla password.

💻 Utilizzo
Per mostrare il modulo di accesso/registrazione tramite Magic Link, inserisci il seguente shortcode in qualsiasi pagina, articolo o widget:

[bp_magic_link]

🌍 Traduzioni
Il plugin è scritto con le stringhe in italiano ma è predisposto per la traduzione. All'interno della cartella /languages è presente il file bp-magic-link.pot.

Puoi utilizzare plugin gratuiti come Loco Translate per creare i file .po e .mo per altre lingue senza toccare il codice sorgente.

⚠️ Avvertenze
Poiché l'intero sistema di login si basa sull'invio di email, è cruciale utilizzare un servizio di invio mail affidabile (es. SendGrid, Mailgun, Brevo) configurato tramite un plugin SMTP, altrimenti le email contenenti i Magic Link potrebbero finire nella cartella Spam o non essere consegnate.

Sviluppato per BuddyPress.


