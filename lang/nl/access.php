<?php

/*
 * Nederlands. Met de hand geschreven.
 *
 * Subuser, rol, SFTP en Wings blijven onvertaald: zo heten ze op de plek waar je
 * ze tegenkomt - Pelicans eigen schermen - en een naam die alleen hier bestaat
 * is een naam die niemand kan opzoeken.
 */

return [
    'nav_label' => 'Servertoegang',
    'title' => 'Servers per rol',
    'subheading' => 'Geef iedereen met een rol toegang tot dezelfde servers.',

    'warning' => 'Dit werkt door Pelicans eigen subusers bij te houden — dezelfde rijen die je met de hand zou toevoegen op de pagina Users van een server, en dat is wat de serverlijst, de rechtencontroles en Wings allemaal lezen. Het raakt alleen rijen aan die het zelf gemaakt heeft: wat jij met de hand toevoegde wordt nooit gewijzigd en nooit verwijderd. Er gaat geen mail uit als een rol iemand een server geeft. Toegang weghalen trekt ook hun SFTP in, en daarvoor is de queue worker nodig die Pelican toch al vraagt.',

    'never' => 'Er is nog niets bijgewerkt. Sla hieronder een koppeling op en het gebeurt meteen, en daarna opnieuw op de timer van het panel.',
    'last_run' => 'Laatste ronde :ago seconden geleden: :added toegevoegd, :removed weggehaald, :held blijven staan.',
    'capped' => 'Te veel in één keer — :pairs toekenningen, en de grens is :max. Er is niets geschreven. Maak een koppeling kleiner: een rol met vijftig mensen en twintig servers is in zijn eentje al duizend toekenningen.',

    'which' => 'De koppelingen',
    'which_helper' => 'Een rol, de servers die iedereen met die rol moet kunnen bereiken, en wat ze daar mogen. Wie twee rollen heeft krijgt alles wat die twee samen geven. Servereigenaren en root admins worden overgeslagen — die hebben al meer dan dit ze kan geven.',
    'add' => 'Rol toevoegen',

    'role' => 'Rol',
    'role_helper' => 'Iedereen die hem heeft, ook wie hem later krijgt.',
    'servers' => 'Servers',
    'servers_helper' => 'De servers die ze krijgen. Eentje hier weghalen neemt die toegang weer terug.',

    'permissions' => 'Wat ze mogen',
    'permissions_helper' => 'Pelicans eigen subuser-rechten. Laat ze staan voor een verstandige set: de console, de aan-uitknoppen, bestanden, back-ups en het activiteitenlog — en niets dat de server, zijn gebruikers, zijn databases of zijn allocaties wijzigt. Connect to websocket zit er altijd bij, want zonder dat verbindt de consolepagina met niets.',

    'save' => 'Opslaan en toepassen',
    'saved' => 'Opgeslagen',
    'saved_body' => ':added toegekend, :removed teruggenomen.',
    'save_failed' => 'Kon niet opslaan',
    'save_failed_disk' => 'De lijst kon niet naar storage geschreven worden. Controleer of storage/app van de gebruiker is waaronder het panel draait.',

    'revoke' => 'Alles terugnemen',
    'revoke_confirm' => 'Alles weghalen wat dit heeft toegekend?',
    'revoke_confirm_helper' => 'Elke subuser-rij die deze pagina gemaakt heeft, op elke server, voor iedereen — en hun SFTP erbij. Rijen die jij met de hand maakte blijven staan. De koppelingen hieronder blijven bestaan, dus de eerstvolgende keer opslaan of de eerstvolgende ronde kent ze weer toe: maak de lijst eerst leeg als je het definitief bedoelt.',
    'revoked' => ':count weggehaald',
    'revoked_body' => 'Alleen rijen die deze pagina zelf gemaakt had. Wat met de hand is toegevoegd staat waar het stond.',
    'revoke_failed' => 'Kon ze niet weghalen',
];
