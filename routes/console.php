<?php

use App\Events\RapportJournalierGenere;
use App\Jobs\CheckExpiringAbonnementsJob;
use App\Jobs\ExpireTicketsJob;
use App\Jobs\GenererVoyagesSemaineJob;
use App\Services\Rapports\RapportJournalierService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $donnees = app(RapportJournalierService::class)->generer(now());
    event(new RapportJournalierGenere($donnees));
})->dailyAt(config('app.rapport_journalier_heure', '23:55'))->name('rapport-journalier');

// Expiration automatique des billets non utilisés 1 heure après l'heure de départ du voyage.
Schedule::job(new ExpireTicketsJob)->everyMinute()->name('expiration-billets');

// Vérification quotidienne des abonnements expirant bientôt (dans 3 jours)
Schedule::job(new CheckExpiringAbonnementsJob)->dailyAt('08:00')->name('verification-expiration-abonnements');

// Génération chaque soir des voyages des 7 prochains jours (liste toujours à jour).
Schedule::job(new GenererVoyagesSemaineJob)->dailyAt('22:00')->name('generation-voyages-semaine');

// Envoi des rappels d'embarquement (15 min avant le départ) toutes les minutes.
Schedule::command('goree:send-reminders')->everyMinute()->name('reminders-embarquement');
