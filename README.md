![Alt text](docs/logo.svg?raw=true "logo")


# Contao Microsoft SSO Bundle
Mit dem Contao Microsoft SSO Bundle der BROCKHAUS AG, können Sie sich ganz bequem per SSO im 
Contao Backend anmelden.

## Wofür ist es gut?
Das Contao Microsoft SSO Bundle sorgt dafür, dass Sie sich per Microsoft SSO bei Ihrem Active Directory 
authentifizieren können und sich damit im Contao Backend anmelden können.</br>
Sie möchten wissen, wie das funktioniert? Dann schauen Sie unter dem Punkt 
"[Wie funktioniert das alles?](#wie-funktioniert-das-alles)" einfach nach.

## Wie kann ich das Bundle installieren und konfigurieren?
1. Laden Sie sich das Bundle in Ihrer Contao Umgebung herunter
2. Erstellen Sie unter "contao/" einen Ordner mit dem Namen "settings" 
3. In diesem Ordner soll sich ein weiterer Ordner mit dem Namen "brockhaus-ag" befinden.
4. Anschließend erstellen Sie in dem Ordner "html/contao/settings/brockhaus-ag" einen weiteren Ordner mit 
   dem Namen "contao-microsoft-sso-bundle".
5. In unserem Bundle, in dem Ordner "settings" finden Sie "example" Dateien. <b>Wichtig ist, 
   dass alle Dateien mit ".json" enden und nicht mit ".jsonc"!</b> 
   1. config.json 
      1. Diese Datei sorgt dafür, dass Sie all Ihre config-Variablen an einem Ort haben.
         In dieser Datei legen Sie also Ihre oAuth Credentials, und die Gruppen Id ab.
   2. settings.json
      1. In dieser Datei wird dafür gesorgt, dass alle SAML Settings in unserem Contao Bundle automatisch geladen 
         werden. <b>Achten Sie darauf, dass die "assertionConsumerService" URL mit "/adfs"endet.</b>
6. Wenn Sie die Dateien angepasst haben, können Sie die Dateien in dem Ordner 
   "html/contao/settings/brockhaus-ag/contao-microsoft-sso-bundle/" ablegen.
7. Sobald Sie alle Schritte beendet haben, können Sie mit dem Punkt "[Ich bin fertig, wie 
   kann ich mich jetzt einloggen?](#ich-bin-fertig-wie-kann-ich-mich-jetzt-einloggen)" weiter machen.

## Ich bin fertig, wie kann ich mich jetzt einloggen?
1. Sie können sich automatisch per SSO einloggen, indem Sie in Ihrem Browser Ihre URL und dann "/adfs" eingeben.
2. Sie sollten nun automatisch eingeloggt sein!

## Wie funktioniert das alles?
Über den Controller "/adfs" werden unterschiedliche Schnittstellen aufgerufen, die dafür sorgen, dass
Sie sich automatisch einloggen können. Doch welche Schnittstellen sind das und wie funktioniert das 
alles?</br>
</br>
Sobald Sie auf die Seite zugreifen, wird mithilfe von Ihren SAML Settngs ein SAMl Request abgesetzt. Dieser leitet Sie 
zu der von Ihnen angegebenen IDP. Sobald Sie sich per SSO erfolgreich angemeldet haben, werden Sie zurück an die "/adfs"
Seite geleitet, welche Sie in der "settings.json" Datei unter "assertionConsumerService", "url" angegeben haben.</br>
</br>
Nachdem der erste Schritt erfolgreich durchgelaufen ist, werden nun die Daten, also die Gruppe aus dem AD, welche von 
dem SAML Request zurückgekommen sind in einer Session gespeichert. Von dort aus wird geprüft, ob der Nutzer, welcher 
sich erst gerade per SSO authentifiziert hat, schon ein back end User bei Contao ist. Falls das nicht der Fall sein 
sollte, wird der User als back end User bei Contao angelegt. </br>
Nach jeder erfolgreichen Authentifizierung wird das Passwort von dem back end User aktualisiert.</br>
Als letztes wird der back end User mithilfe von Symfony, in dem Contao back end eingeloggt und zu der "/contao" Seite 
weitergeleitet</br> 