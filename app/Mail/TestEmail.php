<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

     public function build()
    {
        return $this->subject('Changement de votre e-mail')->view('emails.testEmail');
    }
}
