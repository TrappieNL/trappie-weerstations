# Trappie Weerstations

WordPress-plugin voor een informatieve hobby- en advieswebsite over weerstations. De plugin is geen webshop en bevat bewust geen winkelwagen, checkout, voorraadbeheer of scraping.

## Repository

Deze repository bevat twee onderdelen:

- De plugin **Trappie Weerstations** staat in de hoofdmap.
- Het bijbehorende thema **Trappie Meteo** staat in `themes/trappie-meteo`.

Installeer plugin en thema ieder als een afzonderlijke zip in WordPress. Gebruik daarvoor de losse installatiepakketten en niet de volledige GitHub-repositoryzip.

## Installatie

1. Zip de map `trappie-weerstations`.
2. Ga in WordPress naar **Plugins > Nieuwe plugin > Plugin uploaden**.
3. Upload de zip en activeer de plugin.
4. Ga naar **Weerstations** om weerstations te beheren.
5. Gebruik **Weerstations > Gevonden kandidaten** om crawler- of bezoekersvoorstellen te controleren.
6. Open `/weerstations/` voor het publieke overzicht. De pagina's voor filteren, vergelijken en voorstellen worden automatisch aangemaakt.

## Inhoud

- Publiek custom post type: `weerstation`
- Interne custom post types:
  - `crawler_source`
  - `crawler_candidate`
  - `crawler_observation`
- Taxonomieen:
  - `merk`
  - `sensoren`
  - `connectiviteit`
  - `weerplatformen`
  - `gebruikstype`
- Featured images voor weerstations.
- Archive- en single templates voor het overzicht en weerstation detailpagina's.
- Adminscherm met kandidatenlijst, statussen en acties om een kandidaat om te zetten of te koppelen.

## Shortcodes

```text
[weerstations_overzicht]
[weerstations_filter]
[weerstations_vergelijking]
[weerstation_voorstellen]
```

Voor vergelijking kun je optioneel IDs meegeven:

```text
[weerstations_vergelijking ids="12,18,24"]
```

Of via de URL:

```text
?station_ids=12,18,24
```

## REST API

Namespace:

```text
/wp-json/trappie-weerstations/v1
```

Authenticatie: gebruik een ingelogde WordPress-gebruiker met `edit_posts`, bijvoorbeeld via WordPress Application Passwords. Alle endpoints hebben permission callbacks en voeren sanitizing uit.

### Bron aanmaken of bijwerken

`POST /wp-json/trappie-weerstations/v1/sources`

```bash
curl -u "gebruikersnaam:application-password" \
  -H "Content-Type: application/json" \
  -X POST "https://example.com/wp-json/trappie-weerstations/v1/sources" \
  -d '{
    "title": "Fabrikant voorbeeld",
    "source_url": "https://example.com/weather-station",
    "external_id": "source-123",
    "notes": "Handmatig gekozen bron"
  }'
```

### Kandidaat aanmaken

`POST /wp-json/trappie-weerstations/v1/candidates`

```bash
curl -u "gebruikersnaam:application-password" \
  -H "Content-Type: application/json" \
  -X POST "https://example.com/wp-json/trappie-weerstations/v1/candidates" \
  -d '{
    "merk": "Ecowitt",
    "model": "WS69",
    "omschrijving": "Kandidaat voor handmatige controle.",
    "fabrikant_url": "https://example.com",
    "verkrijgbaar_in_nederland": true,
    "indicatieve_prijsklasse": "150-250 euro",
    "meetwaarden": "Temperatuur, luchtvochtigheid, wind, regen",
    "wifi": true,
    "compatible_weather_underground": true,
    "bron_url": "https://example.com/ws69",
    "betrouwbaarheidsscore": 70
  }'
```

### Kandidaat bijwerken

`PUT /wp-json/trappie-weerstations/v1/candidates/{id}`

```bash
curl -u "gebruikersnaam:application-password" \
  -H "Content-Type: application/json" \
  -X PUT "https://example.com/wp-json/trappie-weerstations/v1/candidates/123" \
  -d '{
    "candidate_status": "controleren",
    "opmerkingen": "Nog specificaties controleren."
  }'
```

Toegestane statussen:

```text
nieuw, controleren, afgekeurd, goedgekeurd, gepubliceerd
```

### Observatie toevoegen

`POST /wp-json/trappie-weerstations/v1/observations`

```bash
curl -u "gebruikersnaam:application-password" \
  -H "Content-Type: application/json" \
  -X POST "https://example.com/wp-json/trappie-weerstations/v1/observations" \
  -d '{
    "candidate_id": 123,
    "source_id": 45,
    "observation_type": "price_range",
    "observed_value": "150-250 euro",
    "source_url": "https://example.com/ws69",
    "notes": "Indicatieve prijs gevonden in bron."
  }'
```

## Beveiliging

- Adminacties gebruiken nonces en capability checks.
- REST endpoints gebruiken permission callbacks.
- Invoer wordt gesanitized voordat deze wordt opgeslagen.
- Output wordt escaped in admin en frontend.
- Bezoekersvoorstellen worden altijd opgeslagen als interne kandidaat en niet direct gepubliceerd.

## Belangrijk

Deze plugin haalt zelf geen externe websites op. Een externe crawler kan via de REST API bronnen, kandidaten en observaties aanleveren, waarna een beheerder de informatie handmatig controleert.
