![Alt text](docs/logo.svg?raw=true "logo")

# **Contao Microsoft SSO Bundle**
Mit dem Contao Microsoft SSO Bundle der BROCKHAUS AG melden Sie sich bequem per SSO im Contao-Backend
oder als Frontend-Mitglied an.

## **Wofür ist das Bundle gut?**
Das Contao Microsoft SSO Bundle ermöglicht eine Authentifizierung per Microsoft SSO bei Ihrem Active 
Directory und eine Anmeldung im Contao-Backend.</br>
Sie möchten wissen, wie das geht? Unter dem Punkt &quot;[Wie funktioniert das?](#wie-funktioniert-das)&quot; 
haben wir alles zusammengefasst.

## **Wie kann ich das Bundle installieren und konfigurieren?**
1. Laden Sie sich das Bundle mit dem Contao Manager in Ihrer Contao Umgebung, oder per composer herunter.
2. Erstellen Sie unter &quot;contao/&quot; einen Ordner mit dem Namen &quot;settings&quot;.
3. In diesem Ordner legen Sie einen weiteren Ordner mit dem Namen &quot;brockhaus-ag&quot; an.
4. Anschließend erstellen Sie in dem Ordner &quot;html/contao/settings/brockhaus-ag&quot; einen weiteren 
   Ordner mit dem Namen &quot;contao-microsoft-sso-bundle&quot;.
5. In dem Ordner &quot;settings&quot; unseres Bundles finden Sie &quot;example&quot;-Dateien. **Wichtig: 
   Alle Dateien müssen die Endung &quot;.json&quot; und nicht &quot;.jsonc&quot; aufweisen!**
6. config.json
   1. Diese Datei sorgt dafür, dass Sie all Ihre config-Variablen an einem Ort haben. In dieser Datei 
      legen Sie also Ihre oAuth Credentials, und die Gruppen Id ab.
7. settings.json
   1. In dieser Datei wird dafür gesorgt, dass alle SAML Settings in unserem Contao Bundle automatisch 
      geladen werden.  **Achten Sie darauf, dass die &quot;assertionConsumerService&quot;-URL mit 
      &quot;/adfs&quot;endet.**
8. Wenn Sie die Dateien angepasst haben, können Sie die Dateien in dem Ordner 
   &quot;html/contao/settings/brockhaus-ag/contao-microsoft-sso-bundle/&quot; ablegen.
9. Sobald alle Schritte abgeschlossen sind, können Sie mit dem Punkt &quot;[Ich bin fertig, wie kann ich 
   mich jetzt einloggen?](#ich-bin-fertig-wie-kann-ich-mich-jetzt-einloggen)&quot; weiter machen.

## **Ich bin fertig, wie kann ich mich jetzt einloggen?**
1. Sie können sich automatisch per SSO einloggen, indem Sie in Ihrem Browser Ihre URL mit dem Zusatz 
   &quot;/adfs&quot; eingeben. Außerdem können Sie sich auch mit dem URL-Zusatz &quot;/adfs_member&quot; 
   im Contao frontend anmelden. Sie müssen das Mitglied später dementsprechend den unterschiedlichen 
   Mitgliedergruppen zuweisen.
2. Sie sollten nun automatisch eingeloggt sein!

## **Wie funktioniert das?**
Über den Controller &quot;/adfs&quot; und &quot;/adfs_member&quot; werden unterschiedliche Schnittstellen 
aufgerufen, die für ein automatisches Einloggen sorgen. Doch welche Schnittstellen sind das und wie 
funktioniert das?</br>
</br>
Sobald Sie auf die Seite zugreifen, wird mithilfe Ihrer SAML Settings ein SAML Request abgesetzt. Dieser 
leitet Sie zu der von Ihnen angegebenen IDP weiter. Sobald Sie sich per SSO erfolgreich angemeldet haben, 
werden Sie zurück an die „/adfs&quot;-Seite geleitet, welche Sie in der „settings.json&quot;-Datei unter 
„assertionConsumerService&quot;, „url&quot; angegeben haben.</br>
</br>
Nachdem der erste Schritt erfolgreich durchgelaufen ist, werden nun die Daten, also die Gruppe aus dem AD, 
welche von dem SAML Request zurückgekommen ist, in einer Session gespeichert. Von dort aus wird geprüft, 
ob der Nutzer, welcher sich erst gerade per SSO authentifiziert hat, schon ein Backend- und Frontend Nutzer 
in Contao ist. Falls das nicht der Fall sein sollte, wird der Nutzer in Contao angelegt.</br>
Nach jeder erfolgreichen Authentifizierung wird das Passwort des Backend- und Frontend Nutzers aktualisiert.
