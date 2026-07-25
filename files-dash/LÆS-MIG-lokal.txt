LOKALT DASHBOARD - én html-fil, ingen server
=============================================

FILEN
-----
dashboard-lokal.html   -> alt er i denne ene fil: udseende, program og data.
                          Dine 27 links fra links.json er allerede lagt ind.

Ingen PHP, ingen links.json, ingen notes.json, ingen config.json.
Filen kan ligge på computeren, på en USB-stick eller i iCloud Drive.


SÅDAN BRUGER DU DEN
-------------------
Åbn: dobbeltklik på filen. Den åbner i browseren.

Se: søgefelt, emneknapper, "+ Tags", fastgjorte links øverst,
    📝 på et kort viser linkets note, 📝 nederst til venstre viser fællesnoten.

Ret: tryk ⚙️ nederst til højre. Her tilføjer, retter, flytter og sletter du
     links, og her skriver du overskrift, undertekst og fællesnote.

Gem: når du har ændret noget, dukker en 💾-knap op nederst til højre
     (og "Gem til fil •" i redigeringspanelet). Tryk på den.

     I Chrome og Edge: du vælger filen første gang, og derefter gemmes
     direkte i den samme fil resten af gangen - som i et almindeligt program.

     I Safari: filen bliver hentet til Overførsler-mappen. Læg den derefter
     oven i den gamle fil. (Bruger du Chrome, slipper du for det trin.)


HVIS DU GLEMMER AT GEMME
------------------------
Ændringer lægges løbende i browserens hukommelse. Næste gang du åbner filen
på samme computer i samme browser, bliver du spurgt, om du vil hente dem frem.
Det er kun en sikkerhedsline - selve dataene bor i html-filen.

Bemærk: Safari rydder ofte den slags hukommelse for filer åbnet direkte fra
disken. Regn derfor ikke med den - tryk "Gem til fil", når du er færdig.


DELING VIA GITHUB PAGES (eller et hvilket som helst websted)
------------------------------------------------------------
Filen kan lægges direkte i et repo og vises via GitHub Pages. Den ser efter,
om den er åbnet fra din egen disk eller fra et websted:

  Åbnet lokalt   -> ⚙️-knappen er der. Du kan rette og gemme.
  Åbnet på nettet -> ⚙️-knappen er skjult. Ren opslagsside for de andre.

Det er den samme fil begge steder - du skal ikke vedligeholde to udgaver.

Arbejdsgang: læg filen i repo-mappen på din Mac, ret den dér, gem, og
publicér med GitHub Desktop. GitHub Pages cacher ca. ti minutter, så en
ændring slår ikke igennem for de andre med det samme.

Vil du undtagelsesvis rette i den, mens den ligger på nettet, så sæt
#rediger bag adressen og genindlæs:

    https://ditnavn.github.io/dashboard/dashboard.html#rediger

Så er ⚙️ fremme igen. Ændringerne kan stadig ikke gemmes på serveren -
"Gem til fil" henter i stedet filen ned, og i Chrome kan du pege direkte
på filen i din repo-mappe. Derefter er det bare en commit i GitHub Desktop.


LINKS TIL LOKALE FILER
----------------------
Fordi dashboardet selv ligger lokalt, kan du skrive relative stier i URL-feltet:

    ../fotolab/index.html
    ./bogbygger/ebog-generator.html

Så virker dashboardet også uden internet - fx fra USB-stikket.


FLYT DATA MELLEM DE TO UDGAVER
------------------------------
Under ⚙️ -> "Data":
  "Hent links.json"      -> henter en links.json, der kan uploades til
                            webhotellet og bruges af PHP-udgaven.
  "Indlæs links.json …"  -> henter en links.json ind (du vælger, om den skal
                            erstatte eller lægges til).

Formatet er det samme i begge udgaver, så du kan gå frem og tilbage.


HVIS DU BRUGER FLERE COMPUTERE
------------------------------
Filen er ét dokument. Ret den ét sted ad gangen, og lad iCloud synkronisere
færdigt, før du åbner den på den anden Mac. Ellers risikerer du, at den ene
version overskriver den anden - præcis som med et Word-dokument.


HVAD ER SKÅRET FRA I FORHOLD TIL PHP-UDGAVEN
--------------------------------------------
- Pinkode og login (overflødigt på din egen computer)
- Billed-feltet (blev alligevel ikke vist på kortene)
- Adskilte datafiler
Alt andet er med: emner, tags, søgning, fastgør, noter, rækkefølge.
