<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LearnerAccountActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $learner;
    public $enrolment;
    public $setupUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($learner, $enrolment, $setupUrl)
    {
        $this->learner = $learner;
        $this->enrolment = $enrolment;
        $this->setupUrl = $setupUrl;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your DirectSkills Learner Account Is Ready')
            ->view('emails.learner-account-activated');
    }
}
