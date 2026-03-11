<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\AttachmentData;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ActivationEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
     
        $this->user = $user;
        
    }

    public function build()
    {
        $url  = URL::temporarySignedRoute(
            'activate', now()->addMinutes(10), ['user' => $this->user->id]);

        return $this->view('emails.activate', ['url' => $url])
            ->with([
                'url' => $url,
               
            ]);
    
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activation Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.activate',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
