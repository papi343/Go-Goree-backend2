<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rapport journalier : corrige l'ancien bug où l'événement était dispatché
// avec un tableau vide codé en dur au lieu d'appeler RapportJournalierService.
Schedule::call(function () {
    $donnees = app(\App\Services\Rapports\RapportJournalierService::class)->generer(now());
    event(new \App\Events\RapportJournalierGenere($donnees));
})->dailyAt(config('app.rapport_journalier_heure', '23:55'))->name('rapport-journalier');

// Expiration automatique des billets 2 heures après l'heure d'embarquement/départ du voyage (qu'ils soient scannés ou non)
Schedule::job(new \App\Jobs\ExpireTicketsJob)->everyMinute()->name('expiration-billets');
