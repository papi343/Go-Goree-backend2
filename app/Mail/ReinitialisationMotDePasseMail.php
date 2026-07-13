<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email contenant le lien (et le jeton) de définition / réinitialisation du
 * mot de passe. Sert à la fois à l'invitation d'un contrôleur créé par un admin
 * et à la réinitialisation classique d'un mot de passe oublié.
 *
 * Envoyé en file d'attente (ShouldQueue) : n'impacte pas le temps de réponse
 * HTTP et bénéficie des tentatives automatiques en cas d'indisponibilité SMTP.
 */
class ReinitialisationMotDePasseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Nombre de tentatives d'envoi avant échec définitif.
     */
    public $tries = 3;

    /**
     * Délai (secondes) entre deux tentatives.
     */
    public $backoff = 10;

    public function __construct(
        public User $user,
        public string $token,
        public bool $invitation = false,
    ) {
        // File d'attente dédiée aux notifications transactionnelles.
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invitation
                ? 'Activez votre compte contrôleur - Go Gorée'
                : 'Réinitialisation de votre mot de passe - Go Gorée',
        );
    }

    public function content(): Content
    {
        $base = rtrim((string) config('app.password_reset_url', rtrim((string) config('app.url'), '/').'/reset-password'), '/');

        $lien = $base.'?token='.$this->token.'&email='.urlencode($this->user->email);

        return new Content(
            view: 'emails.auth.reinitialisation',
            with: [
                'lien' => $lien,
                'token' => $this->token,
                'invitation' => $this->invitation,
                'prenom' => $this->user->prenom,
                'expireMinutes' => (int) config('auth.passwords.users.expire', 60),
            ],
        );
    }
}
