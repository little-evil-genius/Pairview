# Pairview 2.0
Dieses Plugin fügt eurem Forum eine interaktive Pärchenübersicht hinzu. Im ACP kann das Team Kategorien für die Einteilung vorgeben und die User können sich selbstständig eintragen, wenn ihre entsprechende Benutzergruppe die Berechtigung besitzt. Man kann sich immer nur mit seinem aktiven Charakter, mit dem man eingeloggt ist eintragen. Doppelte Einträge sind möglich. Auch mit dem gleichen Partner. Nur nicht zweimal in die gleiche Kategorie.<br>
Das Team kann einstellen, ob Gäste die Icons der Charaktere sehen dürfen oder eine Default Grafik angezeigt werden soll. Außerdem kann das Team einstellen, wie die Icons eingepflegt werden sollen. Es gibt zwei Möglichkeiten:<br>
Möglichkeit 1: normale Links<br>
Bei dieser Variante werden zwei normale Inputfelder (Textfeld) für die Icons angezeigt und ein normaler Link wird eingefügt und gespeichert. Die PHP überprüft, ob der Link über eine SSL-Verschlüsselung aufgerufen wird.<br>
Möglichkeit 2: Upload Funktion<br>
Diese Option kennt man vor allem von der Avatar-Funktion. Bei dieser Variante müssen die Icons vom lokalen Gerät hochgeladen werden. Die hochgeladenen Bilder werden dann auf dem Webspace vom Forum im Unterordner uploads/pairview gespeichert. Für diese Variante kann das Team noch ein paar Einstellungen vornehmen:<br>
- Erlaubte Dateitypen: hier kann das Team festlegen, welche Dateitypen erlaubt sind. Dürfen keine Gif Datein hochgeladen werden sollen, kann man gif entfernen. 
- Icon-Größe: diese Einstellung muss nicht ausgefüllt werden, aber wenn dann kommt eine Fehlermeldung, wenn die hochgeladene Datei nicht der Größe entspricht. 
- Quadratische Icons: falls keine feste Icon Größe festgelegt wurde, weil man seine User nicht zwingen möchte die Datein noch zu bearbeiten z.B., aber möchte dass die Icons dennoch quadratisch sind, weil sie so vom Design her nicht verzogen werden kann man auch das festlegen.
- Maximale Datei-Größe: die Icons werden auf den Webspace geladen und verbrauchen somit Speicherplatz. Damit User es nicht übertreiben kann man ein maximal Wert angeben. 5120 KB entsprechen 5 MB. Ein Online-Rechner findet ihr beispielsweise hier: <a href="https://www.online-rechner.net/datenmenge/kb-mb/">KiloByte (KB) in MegaByte (MB) umrechnen</a>.<br>
So bald ein Account gelöscht wird, werden nicht nur alle Einträge von und mit diesem Account gelöscht und auch vom Webspace entfernt.<br><br>
<b>HINWEIS:</b><br>
Per Einstellung kann geregelt werden, ob das Listen-Menü angezeigt werden soll. Es kann ausgewählt werden, dass ein eigenes Tpl gelanden werden soll oder ob das <a href="https://github.com/ItsSparksFly/mybb-lists">Automatische Listen-Plugin von sparks fly</a> verwendet wird. 

# Empfehlungen
- <a href="https://github.com/MyBBStuff/MyAlerts" target="_blank">MyAlerts</a> von EuanT 

# Datenbank-Änderungen
hinzugefügte Tabelle:
- PRÄFIX_pairs

# Einstellungen - Postpartnersuche
- Erlaubte Gruppen
- Kategorien
- Avatar verstecken
- Icon ausblenden
- Standard-Icon
- Upload-System
- Erlaubte Dateitypen
- Icon-Größe
- Quadratische Icons
- Maximale Datei-Größe
- Listen PHP
- Listen Menü
- Listen Menü Template

# Neue Template-Gruppe innerhalb der Design-Templates
- Pärchenübersicht

# Neue Templates (nicht global!)
- pairview
- pairview_add_link
- pairview_add_upload
- pairview_category
- pairview_edit_link
- pairview_edit_upload
- pairview_pair<br><br>
<b>HINWEIS:</b><br>
Alle Templates wurden ohne Tabellen-Struktur gecodet. Das Layout wurde auf ein MyBB Default Design angepasst.

# Neues CSS - pairview.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern, bevor man dies im Board mit der Untersuchungsfunktion bearbeiten will, da es dann passieren kann, dass das CSS für dieses Plugin in einen anderen Stylesheet gerutscht ist, obwohl es im ACP richtig ist.

# neuer Ordner
Es wurde ein neuer Ordner mit dem Namen pairview im Ordner uploads erstellt. In diesem Ordner werden die Icons hochgeladen, falls die Upload Funktion aktiviert wurde. 

# Account löschen
Damit die Löschung der Daten in der Datenbank und von den Bildern vom Webspace richtig funktioniert müssen Accounts über das Popup "Optionen" im ACP gelöscht werden. Im ACP werden alle Accounts unter dem Reiter Benutzer & Gruppen > Benutzer aufgelistet. Und bei jedem Account befindet sich rechts ein Optionen Button. Wenn man diesen druckt erscheint eine Auswahl von Möglichkeiten. Über diese Variante müssen Accounts gelöscht werden, damit das automatische Löschen der Pairview Informationen funktioniert.

# Links
- euerforum.de/misc.php?action=pairview

# Demo
<img src="https://stormborn.at/plugins/pairview.png">
<img src="https://stormborn.at/plugins/pairview_upload.png">
<img src="https://stormborn.at/plugins/pairview_link.png">
<img src="https://stormborn.at/plugins/pairview_edit_upload.png">
<img src="https://stormborn.at/plugins/pairview_edit_link.png">
