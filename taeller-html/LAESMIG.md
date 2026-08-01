# Tæller — opsætning og test

## 1. På webhotellet

Læg de to filer i en mappe, fx `/t/`:

```
/t/p.php
/t/kig.php
```

`data/`-mappen bliver oprettet automatisk første gang der kommer et ping.
Der skal ikke oprettes noget manuelt.

Ret koden øverst i `kig.php`:

```php
const PIN = '1234';        // sæt din egen
```

Tjek at det virker ved at åbne `https://ditdomaene.dk/t/p.php?id=test&e=open&t=Test`
i browseren. Du skal se en blank/tom side — det er den lille GIF. Åbn derefter
`kig.php`, log ind, og se om `test` står på listen.

## 2. I HTML-filen

Snippetten skal ind **lige før `</body>`**, altså allernederst i filen.

Åbn din singlefile-HTML i en teksteditor (VS Code) og find de sidste linjer.
Der står typisk noget i retning af:

```html
    </script>
  </body>
</html>
```

Indsæt blokken fra `snippet.html` mellem `</script>` og `</body>`:

```html
    </script>

    <!-- TÆLLER — indsættes lige før </body> -->
    <script>
    (function () { ... })();
    </script>

  </body>
</html>
```

Placeringen nederst betyder at afspilleren er færdig med at loade før
tælleren gør noget som helst. Skulle snippetten mod forventning fejle,
er filen allerede fuldt funktionsdygtig.

**Bemærk:** i store filer med base64 kan `</body>` ligge langt nede efter
en meget lang linje. Brug editorens søgefunktion (Cmd+F) og søg efter
`</body>` frem for at scrolle.

## 3. De to linjer du skal rette

I snippetten:

```js
var ID   = "SKIFT-MIG";                            // <<< RET
var BASE = "https://stat.ditdomaene.dk/t/p.php";   // <<< RET
```

`BASE` er den samme i alle filer. `ID` skal være **unikt pr. fil** og
**aldrig ændres bagefter** — det er nøglen som tallene hænger på.

Forslag til navngivning:

```
lyd-mosevej-2026-a7f3
alb-byvandring-nord-2026-c19b
art-metalvarefabrik-2026-4d2e
```

Altså: type, kort emne, år, fire tilfældige tegn. De fire tegn til sidst
er ikke pynt — de sikrer at to filer om samme emne aldrig kolliderer.

Snippetten gør ingenting hvis du glemmer at rette `ID`. Det er med vilje:
så får du en tom række på dashboardet i stedet for at flere filer smelter
sammen under `SKIFT-MIG`.

## 4. Hvad der tælles automatisk

| Hændelse | Hvornår | Kræver ændringer? |
|---|---|---|
| `open` | Filen åbnes | Nej |
| `play` | Der trykkes afspil på et `<audio>` eller `<video>` | Nej |
| `image` | Manuelt kald | Ja, se nedenfor |

Afspilning fanges automatisk, uanset hvordan din afspiller er bygget, så
længe der er et rigtigt `<audio>`- eller `<video>`-element i filen.

Hvis du på et tidspunkt vil tælle billedvisninger i et album, kalder du
bare denne linje i galleriets lightbox-funktion:

```js
window.__taeller && window.__taeller("image");
```

Hver hændelse tælles kun **én gang pr. fane**. Genindlæser du siden fem
gange under test, får du ét `open`. Luk fanen helt for at tælle igen.

## 5. Under testen

Du kommer selv til at fylde en del i tallene mens du prøver. Brug
"Nulstil alt" i `kig.php` når du er færdig med at teste, så starter
statistikken rent når filerne går i luften.

## 6. Hvad der bevidst ikke gemmes

Ingen IP-adresser, ingen cookies, ingen User-Agent, intet der kan pege på
en person. Loggen indeholder kun tidspunkt, fil-ID og hændelsestype.
Derfor er der hverken samtykke- eller cookiebanner-problematik.
