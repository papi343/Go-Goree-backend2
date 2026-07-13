<?php

namespace App\Mail;

use App\Models\DemandeResidence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email détaillé envoyé aux administrateurs à la réception d'une nouvelle
 * demande de résidence (en complément de la notification temps réel).
 */
class NouvelleDemandeResidenceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 10;

    public function __construct(public DemandeResidence $demande)
    {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle demande de résidence - Go Gorée',
        );
    }

    public function content(): Content
    {
        $demandeur = $this->demande->user;

        return new Content(
            view: 'emails.residence.nouvelle_demande',
            with: [
                'demande' => $this->demande,
                'nomComplet' => $demandeur ? "{$demandeur->prenom} {$demandeur->nom}" : 'Inconnu',
                'email' => $demandeur?->email,
            ],
        );
    }
}
