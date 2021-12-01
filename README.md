![Alt text](docs/logo.svg?raw=true "logo")


# Contao Microsoft SSO Bundle
Mit dem Contao Microsoft SSO Bundle der BROCKHAUS AG, können Sie sich ganz bequem per SSO im 
Contao Backend anmelden.

## Wie kann ich das Bundle installieren und Konfigurieren?
1. Laden Sie sich das Bundle in Ihrer Contao Umgebung herunter
2. Erstellen Sie unter "vendor/brockhaus-ag/contao-microsoft-sso-bundle/Resources/settings" 
   zwei neue Dateien. <b>Wichtig ist, dass alle Dateien mit ".json" enden und nicht mit 
   ".jsonc"!</b> 
   1. config.json 
      1. Diese Datei sorgt dafür, dass Sie all Ihre config-Variablen an einem Ort haben.
         In dieser Datei legen Sie also Ihre oAuth Credentials, und die Gruppen Id ab.
   2. settings.json
      1. In dieser Datei wird dafür gesorgt, dass alle SAML Settings in unserem Contao Bundle
         automatisch geladen werden.

## Ich bin fertig, wie kann ich mich jetzt einloggen?
1. Sie können sich automatisch per SSO einloggen, indem Sie in Ihrem Browser Ihre URL und dann 
   "/adfs" eingeben.
2. Sie sollten nun automatisch eingeloggt sein. 
