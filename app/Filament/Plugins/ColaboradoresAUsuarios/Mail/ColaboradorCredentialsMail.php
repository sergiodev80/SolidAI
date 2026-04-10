<?php

namespace App\Filament\Plugins\ColaboradoresAUsuarios\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ColaboradorCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu acceso ha sido creado - Contraseña provisional',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.colaborador-credentials',
            with: [
                'email' => $this->email,
                'password' => $this->password,
                'loginUrl' => route('filament.admin.auth.login'),
            ],
        );
    }
}
