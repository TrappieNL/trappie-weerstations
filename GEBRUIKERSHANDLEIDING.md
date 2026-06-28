# Gebruikershandleiding Trappie Weerstations

## 1. Doel van de plugin

Trappie Weerstations is bedoeld voor een informatieve hobby- en advieswebsite over weerstations. De plugin beheert weerstations, technische specificaties, kandidaten uit externe bronnen en bezoekersvoorstellen.

De plugin is geen webshop. Er zijn geen winkelwagen-, checkout-, voorraad- of betaalfuncties. De plugin voert zelf geen scraping uit.

## 2. Belangrijkste onderdelen

Onder **Weerstations** in het WordPress-beheermenu vind je:

- Alle gepubliceerde en concept-weerstations.
- De taxonomieen voor merk, sensoren, connectiviteit, weerplatformen en gebruikstype.
- Gevonden kandidaten voor handmatige controle.
- Crawlerbronnen en observaties.
- Deze gebruikershandleiding.

De interne crawleronderdelen zijn niet publiek zichtbaar op de website.

## 3. Een weerstation handmatig toevoegen

1. Open **Weerstations > Nieuw weerstation**.
2. Vul een duidelijke titel in, meestal merk en model.
3. Schrijf de volledige omschrijving in de WordPress-editor.
4. Vul de velden onder **Weerstation gegevens** in.
5. Kies rechts een **Uitgelichte afbeelding**.
6. Voeg eventueel extra foto's toe onder **Weerstation afbeeldingen**.
7. Selecteer toepasselijke taxonomieen.
8. Kies **Publiceren**.

Gebruik bij twijfel een concept en controleer fabrikant- en broninformatie voordat je publiceert.

## 4. Afbeeldingen beheren

### Hoofdafbeelding

Gebruik **Uitgelichte afbeelding** voor het belangrijkste beeld. Dit beeld verschijnt op overzichtskaarten en bovenaan de detailpagina.

### Extra afbeeldingen

1. Open het betreffende weerstation.
2. Ga naar **Weerstation afbeeldingen**.
3. Klik op **Afbeeldingen kiezen**.
4. Upload nieuwe afbeeldingen of selecteer bestaande afbeeldingen uit de mediabibliotheek.
5. Selecteer meerdere afbeeldingen en klik op **Afbeeldingen gebruiken**.
6. Verwijder ongewenste afbeeldingen met **Verwijderen** onder de miniatuur.
7. Klik op **Bijwerken**.

Als geen uitgelichte afbeelding is ingesteld, gebruikt de detailpagina de eerste galerijafbeelding als hoofdbeeld.
Dezelfde afbeelding wordt dan automatisch gebruikt op publieke overzichten en op de startpagina van het thema.

De technische kenmerken staan op de detailpagina in de vaste volgorde van de plugin, als rustige regels onder elkaar.

Gebruik alleen afbeeldingen waarvoor je toestemming of gebruiksrecht hebt. Voeg in de mediabibliotheek een beschrijvende alternatieve tekst toe voor toegankelijkheid.

## 5. Kandidaten controleren

Open **Weerstations > Gevonden kandidaten**. Controleer minimaal:

- Merk en model.
- Of het product werkelijk een weerstation is.
- Fabrikant- en bron-URL.
- Beschikbaarheid in Nederland.
- Indicatieve prijsklasse.
- Meetwaarden en sensoren.
- Connectiviteit en ondersteunde weerplatformen.
- Volledigheid van de omschrijving.

Kandidaatstatussen:

- **Nieuw**: nog niet gecontroleerd.
- **Controleren**: handmatige controle is bezig.
- **Afgekeurd**: niet geschikt of onvoldoende betrouwbaar.
- **Goedgekeurd**: gecontroleerd en geschikt.
- **Gepubliceerd**: gekoppeld aan een gepubliceerd weerstation.

## 6. Kandidaten in bulk publiceren

1. Open **Weerstations > Gevonden kandidaten**.
2. Selecteer de gewenste kandidaten met de selectievakjes.
3. Kies **Maak en publiceer weerstations** bij Bulkacties.
4. Klik op **Toepassen**.
5. Controleer de melding met aantallen gepubliceerd, overgeslagen en mislukt.

Een reeds gepubliceerd gekoppeld weerstation wordt niet dubbel aangemaakt. Een gekoppeld concept kan met dezelfde bulkactie worden gepubliceerd.

## 7. Prijzen controleren

De plugin bevat geen vaste of standaardprijs. De waarde in **Indicatieve prijsklasse** komt uit de opgeslagen kandidaat- of crawlinformatie, of is handmatig ingevoerd.

Als veel weerstations exact `EUR 14,99` tonen, komt die herhaalde waarde dus uit de aangeleverde data. Controleer of de crawler mogelijk een accessoireprijs, verzendprijs of vanaf-prijs heeft gelezen. Corrigeer de waarde handmatig voordat je publiceert.

Gebruik bij voorkeur een bandbreedte, bijvoorbeeld `EUR 150 - EUR 200`, en behandel deze altijd als indicatie.

## 8. Omschrijvingen en afkortingen

Op overzichtskaarten toont de plugin bewust een korte samenvatting. Deze eindigt met drie puntjes en een link **Bekijk alle informatie**.

De detailpagina bevat geen limiet van de plugin en toont de volledige opgeslagen WordPress-inhoud. Eindigt de tekst op de detailpagina ook halverwege met drie puntjes, dan is de kandidaat- of crawlinformatie zelf al afgekapt. Open in dat geval het weerstation en vul de omschrijving handmatig aan.

Je kunt het veld **Samenvatting** in WordPress gebruiken om zelf te bepalen welke korte tekst op de overzichtskaart verschijnt.

## 9. Publieke pagina's

De plugin ondersteunt:

```text
[weerstations_overzicht]
[weerstations_filter]
[weerstations_vergelijking]
[weerstation_voorstellen]
```

De openbare archiefpagina is standaard bereikbaar via `/weerstations/`. Filter-, vergelijk- en voorstelfuncties worden tijdens installatie als pagina aangemaakt.

## 10. Bezoekersvoorstellen

Een bezoeker kan via `[weerstation_voorstellen]` een model voorstellen. Dit wordt altijd als interne kandidaat opgeslagen en nooit automatisch gepubliceerd. Controleer het voorstel via **Gevonden kandidaten**.

## 11. Externe crawler en REST API

Een externe crawler kan bronnen, kandidaten en observaties via de REST API aanleveren. Authenticatie en een WordPress-gebruiker met voldoende rechten zijn verplicht.

Namespace:

```text
/wp-json/trappie-weerstations/v1
```

De plugin bezoekt of scrapt zelf geen externe websites. Technische voorbeeldcalls staan in `README.md`.

## 12. Plugin bijwerken

Maak vooraf een databaseback-up. De weerstations en kandidaten staan in de WordPress-database en blijven bij het deactiveren van de plugin bewaard.

1. Deactiveer de oude pluginversie.
2. Verwijder alleen de oude pluginbestanden via WordPress.
3. Upload het nieuwe pluginpakket.
4. Activeer de nieuwe versie.
5. Controleer **Weerstations > Handleiding** en een bestaande detailpagina.

Activeer nooit twee exemplaren van Trappie Weerstations tegelijk.

## 13. Problemen oplossen

### Doelmap bestaat al

De hosting heeft de oude pluginmap niet volledig verwijderd. Gebruik het installatiepakket met een nieuwe mapnaam of verwijder de achtergebleven map via het hostingbestandsbeheer.

### Het beheermenu lijkt te verdwijnen

Vanaf versie 1.3.0 staan crawlerbronnen, kandidaten en observaties onder het hoofdmenu **Weerstations**. Controleer of slechts een pluginversie actief is en vernieuw het beheerscherm.

### Afbeeldingen verschijnen niet

- Controleer of het weerstation is bijgewerkt na het kiezen van afbeeldingen.
- Controleer of de mediabestanden echte afbeeldingen zijn.
- Leeg eventuele WordPress- of hostingcache.
- Controleer of het thema `get_header()`, `get_footer()` en `wp_head()` correct gebruikt.

### Verkeerde prijs of afgekorte detailtekst

Dit betreft opgeslagen brondata. Corrigeer het weerstation of de kandidaat handmatig en onderzoek vervolgens de externe crawlerconfiguratie.

## 14. Disclaimer

Alle informatie is informatief hobbyadvies. Prijzen, compatibiliteit, beschikbaarheid en technische specificaties kunnen veranderen. Controleer belangrijke gegevens altijd bij de fabrikant of leverancier.
